<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Hashing\HashManager;
use InvalidArgumentException;

final class TenantPasswordBrokerManager extends PasswordBrokerManager
{
    /**
     * Resolve the given broker.
     */
    protected function resolve(?string $name = null): PasswordBroker
    {
        $name = $name ?: $this->getDefaultDriver();

        $config = $this->getConfig($name);

        throw_if(is_null($config), new InvalidArgumentException("Password resetter [{$name}] is not defined."));

        return $this->brokers[$name] = $this->createTenantBroker($config);
    }

    /**
     * Create a token repository instance based on the given configuration with tenant support.
     */
    protected function createTokenRepository(array $config): DatabaseTokenRepository
    {
        $key = $this->app->make(Repository::class)->get('app.key');

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(mb_substr($key, 7));
        }

        return new class($this->app->make(ConnectionResolverInterface::class)->connection($config['connection'] ?? null), $this->app->make(HashManager::class), $config['table'], $key, $config['expire'], $config['throttle'] ?? 0) extends DatabaseTokenRepository
        {
            /**
             * Create a new token record with tenant support.
             */
            public function create($user): string
            {
                $email = $user->getEmailForPasswordReset();

                $this->deleteExisting($user);

                $token = $this->createNewToken();

                $this->getTable()->insert($this->getPayload($email, $token, $user));

                return $token;
            }

            /**
             * Build the record payload for the table with tenant support.
             */
            protected function getPayload(string $email, string $token, $user): array
            {
                return [
                    'organization_id' => $user->organization_id,
                    'email' => $email,
                    'token' => $this->hasher->make($token),
                    'created_at' => CarbonImmutable::now(),
                ];
            }

            /**
             * Determine if a token record exists and is valid with tenant support.
             */
            public function exists($user, string $token): bool
            {
                $record = (array) $this->getTable()
                    ->where('organization_id', $user->organization_id)
                    ->where('email', $user->getEmailForPasswordReset())
                    ->first();

                return $record &&
                       ! $this->tokenExpired($record['created_at']) &&
                       $this->hasher->check($token, $record['token']);
            }

            /**
             * Delete a token record by user with tenant support.
             */
            public function delete($user): void
            {
                $this->deleteExisting($user);
            }

            /**
             * Delete all existing reset tokens from the database with tenant support.
             */
            protected function deleteExisting($user): int
            {
                return $this->getTable()
                    ->where('organization_id', $user->organization_id)
                    ->where('email', $user->getEmailForPasswordReset())
                    ->delete();
            }

            /**
             * Delete expired tokens with tenant support.
             */
            public function deleteExpired(): void
            {
                $expiredAt = Carbon::now()->subSeconds($this->expires);

                $this->getTable()->where('created_at', '<', $expiredAt)->delete();
            }
        };
    }

    /**
     * Create a tenant-aware password broker.
     */
    private function createTenantBroker(array $config): PasswordBroker
    {
        return new \Illuminate\Auth\Passwords\PasswordBroker(
            $this->createTokenRepository($config),
            $this->app->make(Factory::class)->createUserProvider($config['provider'] ?? null)
        );
    }
}

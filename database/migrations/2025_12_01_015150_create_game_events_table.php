<?php

declare(strict_types=1);

use App\Enums\EventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Event details
            $table->string('type')->default(EventType::Manual->value);
            $table->string('title');
            $table->text('description')->nullable();

            // Event timing
            $table->timestamp('occurred_at');
            $table->integer('priority')->default(0);

            // Event metadata
            $table->json('data')->nullable();

            // Processing status
            $table->boolean('is_processed')->default(false);
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('game_id');
            $table->index('type');
            $table->index('created_by_user_id');
            $table->index(['game_id', 'occurred_at']);
            $table->index(['game_id', 'type']);
            $table->index('is_processed');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_events');
    }
};

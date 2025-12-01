<?php

declare(strict_types=1);

use App\Enums\GamePhase;
use App\Enums\GameStatus;
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
        Schema::create('games', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();

            // Game status and phase
            $table->string('status')->default(GameStatus::Setup->value);
            $table->string('current_phase')->default(GamePhase::Setup->value);

            // State metadata - JSON to store additional phase-specific data
            $table->json('state_metadata')->nullable();

            // Game timing
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->timestamp('setup_started_at')->nullable();
            $table->timestamp('pre_game_started_at')->nullable();
            $table->timestamp('game_started_at')->nullable();
            $table->timestamp('game_ended_at')->nullable();
            $table->timestamp('post_game_started_at')->nullable();

            // Configuration and rules (JSON for flexibility)
            $table->json('config')->nullable();
            $table->json('rules')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('slug');
            $table->index('status');
            $table->index('organization_id');
            $table->index('current_phase');
            $table->index(['organization_id', 'current_phase']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};

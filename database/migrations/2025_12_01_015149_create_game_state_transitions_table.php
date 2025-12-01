<?php

declare(strict_types=1);

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
        Schema::create('game_state_transitions', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Transition details
            $table->string('from_phase');
            $table->string('to_phase');

            // Transition metadata
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();

            // Validation and approval
            $table->boolean('is_valid')->default(true);
            $table->text('validation_notes')->nullable();

            $table->timestamp('transitioned_at');
            $table->timestamps();

            // Indexes
            $table->index('game_id');
            $table->index('user_id');
            $table->index(['game_id', 'transitioned_at']);
            $table->index('from_phase');
            $table->index('to_phase');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_state_transitions');
    }
};

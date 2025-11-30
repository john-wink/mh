<?php

declare(strict_types=1);

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// Create a test model class that uses the UuidTrait
beforeEach(function (): void {
    // Create test table
    Schema::create('test_uuid_models', function (Blueprint $table): void {
        $table->id();
        $table->uuid('uuid')->unique();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('test_uuid_models');
});

// Helper function to create a test model class
function createTestModel(): Model
{
    return new class() extends Model
    {
        use HasFactory;
        use UuidTrait;

        protected $table = 'test_uuid_models';

        protected $fillable = ['name', 'uuid'];
    };
}

// Test automatic UUID generation on model creation
test('automatically generates UUID7 on model creation', function (): void {
    $model = createTestModel();
    $model->name = 'Test Model';
    $model->save();

    expect($model->uuid)
        ->not->toBeEmpty()
        ->toBeString();

    // Verify it's a valid UUID format
    expect(Str::isUuid($model->uuid))->toBeTrue();
});

// Test UUID is not overwritten if already set
test('does not overwrite UUID if already set before creation', function (): void {
    $customUuid = (string) Str::uuid7();
    $model = createTestModel();
    $model->uuid = $customUuid;
    $model->name = 'Test Model';
    $model->save();

    expect($model->uuid)->toBe($customUuid);
});

// Test UUID cannot be changed after creation
test('prevents UUID modification after creation', function (): void {
    $model = createTestModel();
    $model->name = 'Test Model';
    $model->save();

    $originalUuid = $model->uuid;

    // Attempt to change UUID
    $model->uuid = (string) Str::uuid7();
    $model->save();

    // UUID should remain unchanged
    expect($model->fresh()->uuid)->toBe($originalUuid);
});

// Test route key name is set to uuid
test('uses uuid as route key name', function (): void {
    $model = createTestModel();

    expect($model->getRouteKeyName())->toBe('uuid');
});

// Test UUID is generated on initialization for new instances
test('generates UUID on initialization for new instances', function (): void {
    $model = createTestModel();

    // UUID should be generated even before saving
    expect($model->uuid)
        ->not->toBeEmpty()
        ->toBeString();
});

// Test mass assignment with UUID
test('handles mass assignment with explicit UUID', function (): void {
    $customUuid = (string) Str::uuid7();
    $model = createTestModel();
    $model->fill([
        'name' => 'Test Model',
        'uuid' => $customUuid,
    ]);
    $model->save();

    expect($model->uuid)->toBe($customUuid);
});

// Test mass assignment without UUID
test('generates UUID with mass assignment when not provided', function (): void {
    $model = createTestModel();
    $model->fill([
        'name' => 'Test Model',
    ]);
    $model->save();

    expect($model->uuid)
        ->not->toBeEmpty()
        ->toBeString();
});

// Test UUID uniqueness across multiple model creations
test('generates unique UUIDs for multiple models', function (): void {
    $model1 = createTestModel();
    $model1->name = 'Model 1';
    $model1->save();

    $model2 = createTestModel();
    $model2->name = 'Model 2';
    $model2->save();

    $model3 = createTestModel();
    $model3->name = 'Model 3';
    $model3->save();

    expect($model1->uuid)
        ->not->toBe($model2->uuid)
        ->and($model2->uuid)->not->toBe($model3->uuid)
        ->and($model1->uuid)->not->toBe($model3->uuid);
});

// Test UUID format is valid UUID7
test('generated UUID is a valid UUID7 format', function (): void {
    $model = createTestModel();
    $model->name = 'Test Model';
    $model->save();

    // UUID7 should be a valid UUID
    expect(Str::isUuid($model->uuid))->toBeTrue();

    // UUID7 format: xxxxxxxx-xxxx-7xxx-xxxx-xxxxxxxxxxxx
    // The version field (7) should be in the 13th character position
    $parts = explode('-', $model->uuid);
    expect($parts[2][0])->toBe('7'); // Version 7
});

// Test UUID persistence in database
test('UUID is persisted correctly in database', function (): void {
    $model = createTestModel();
    $model->name = 'Test Model';
    $model->save();

    $uuid = $model->uuid;

    // Fetch from database
    $fetchedModel = $model::where('uuid', $uuid)->first();

    expect($fetchedModel)->not->toBeNull()
        ->and($fetchedModel->uuid)->toBe($uuid);
});

// Test finding model by UUID
test('can find model using UUID', function (): void {
    $model = createTestModel();
    $model->name = 'Test Model';
    $model->save();

    $foundModel = $model::where('uuid', $model->uuid)->first();

    expect($foundModel)->not->toBeNull()
        ->and($foundModel->id)->toBe($model->id)
        ->and($foundModel->name)->toBe($model->name);
});

// Test UUID with model update operations
test('UUID remains unchanged during model updates', function (): void {
    $model = createTestModel();
    $model->name = 'Original Name';
    $model->save();

    $originalUuid = $model->uuid;

    // Update the model
    $model->name = 'Updated Name';
    $model->save();

    expect($model->uuid)->toBe($originalUuid)
        ->and($model->fresh()->uuid)->toBe($originalUuid);
});

// Test empty UUID is replaced on save
test('empty UUID is replaced with generated UUID on save', function (): void {
    $model = createTestModel();
    $model->uuid = ''; // Explicitly set to empty
    $model->name = 'Test Model';
    $model->save();

    expect($model->uuid)
        ->not->toBeEmpty()
        ->toBeString();
});

// Test null UUID is replaced on save
test('null UUID is replaced with generated UUID on save', function (): void {
    $model = createTestModel();
    $model->uuid = null; // Explicitly set to null
    $model->name = 'Test Model';
    $model->save();

    expect($model->uuid)
        ->not->toBeEmpty()
        ->toBeString();
});

// Test trait works with model refresh
test('UUID persists through model refresh', function (): void {
    $model = createTestModel();
    $model->name = 'Test Model';
    $model->save();

    $uuid = $model->uuid;
    $model->refresh();

    expect($model->uuid)->toBe($uuid);
});

// Test UUID is set before saving (via initializeUuidTrait)
test('UUID is available immediately on new model instance', function (): void {
    $model = createTestModel();

    // UUID should be available even before setting any attributes
    expect($model->uuid)
        ->not->toBeEmpty()
        ->toBeString();
});

// Test creating multiple models in sequence
test('creates multiple models with unique UUIDs in sequence', function (): void {
    $uuids = [];

    for ($i = 0; $i < 5; $i++) {
        $model = createTestModel();
        $model->name = "Model {$i}";
        $model->save();
        $uuids[] = $model->uuid;
    }

    // All UUIDs should be unique
    expect(count($uuids))->toBe(count(array_unique($uuids)));
});

// Test UUID format consistency
test('all generated UUIDs follow consistent format', function (): void {
    $models = [];

    for ($i = 0; $i < 3; $i++) {
        $model = createTestModel();
        $model->name = "Model {$i}";
        $model->save();
        $models[] = $model;
    }

    foreach ($models as $model) {
        // Each UUID should be 36 characters (standard UUID format)
        expect(mb_strlen($model->uuid))->toBe(36)
            ->and(Str::isUuid($model->uuid))->toBeTrue();
    }
});

// Test that attempting to update UUID directly doesn't work
test('direct UUID update attempt is prevented', function (): void {
    $model = createTestModel();
    $model->name = 'Test Model';
    $model->save();

    $originalUuid = $model->uuid;
    $newUuid = (string) Str::uuid7();

    // Attempt direct update
    $model->update(['uuid' => $newUuid]);

    // UUID should remain the original
    expect($model->fresh()->uuid)->toBe($originalUuid);
});

// Test UUID with model replication
test('replicated model gets new UUID', function (): void {
    $model = createTestModel();
    $model->name = 'Original Model';
    $model->save();

    $originalUuid = $model->uuid;

    // Replicate the model
    $replica = $model->replicate();
    $replica->save();

    expect($replica->uuid)
        ->not->toBe($originalUuid)
        ->and(Str::isUuid($replica->uuid))->toBeTrue();
});

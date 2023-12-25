<?php

declare(strict_types=1);

namespace Tests\Unit\Models\User;

use App\Core\Formatter\ExceptionErrorCode;
use App\Core\Formatter\ExceptionMessage\ExceptionMessageStandard;
use App\Exceptions\Models\ModelNotFoundException;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class User_FindByIDOrFail_Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'id' => $this->faker()->numberBetween(1, 100),
        ]);
    }

    #[Test]
    public function should_successfully_return_user_instance(): void
    {
        // Act
        $user = User::findByIDOrFail($this->user->id);


        // Assert
        $this->assertTrue($user->is($this->user));
    }

    #[Test]
    public function should_throw_model_not_found_exception_when_id_is_not_found(): void
    {
        // Assert
        $expectedException = new ModelNotFoundException(new ExceptionMessageStandard(
            'User ID is not found',
            ExceptionErrorCode::MODEL_NOT_FOUND->value,
        ));
        $this->expectExceptionObject($expectedException);


        // Act
        User::findByIDOrFail(1000);
    }
}

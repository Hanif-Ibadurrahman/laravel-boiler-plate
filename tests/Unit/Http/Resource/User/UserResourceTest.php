<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resource\User;

use App\Core\Date\DatetimeFormat;
use App\Http\Resources\User\UserResource;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_return_right_arrayable_format(): void
    {
        // Arrange
        /** @var User */
        $user = User::factory()->create();


        // Act
        $result = json_decode(UserResource::make($user)->toJson(), true);


        // Assert
        $this->assertEquals([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'createdAt' => $user->created_at->format(DatetimeFormat::ISO_WITH_MILLIS->value),
            'updatedAt' => $user->updated_at->format(DatetimeFormat::ISO_WITH_MILLIS->value),
        ], $result);
    }

    #[Test]
    public function should_return_right_arrayable_format_when_nullable_date_is_null(): void
    {
        // Arrange
        /** @var User */
        $user = User::factory()->create([
            'created_at' => null,
            'updated_at' => null,
        ]);


        // Act
        $result = json_decode(UserResource::make($user)->toJson(), true);


        // Assert
        $this->assertEquals([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'createdAt' => null,
            'updatedAt' => null,
        ], $result);
    }
}

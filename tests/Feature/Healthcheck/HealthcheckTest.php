<?php

namespace Tests\Feature\Healthcheck;

use App\Core\Formatter\ExceptionMessage\ExceptionMessageGeneric;
use App\Core\Healthcheck\HealthcheckCoreContract;
use App\Core\Healthcheck\ValueObject\HealthcheckResponse;
use App\Core\Healthcheck\ValueObject\HealthcheckStatus;
use App\Core\Logger\Message\LoggerMessageFactoryContract;
use Exception;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\BaseFeatureTestCase;
use Tests\Helper\MockInstance\MockerLoggerMessageFactory;

class HealthcheckTest extends BaseFeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->instance(HealthcheckCoreContract::class, $this->mock(HealthcheckCoreContract::class));
        $this->instance(
            LoggerMessageFactoryContract::class,
            $this->mock(LoggerMessageFactoryContract::class)
        );
        Log::partialMock();
    }

    #[Test]
    public function should_show_500_when_generic_error_is_thrown()
    {
        // Arrange
        $logInfoMessage = $this->faker->sentence;
        $logErrorMessage = $this->faker->sentence;


        // Assert
        $mockException = new Exception($this->faker->sentence);
        $mockCore = $this->mock(
            HealthcheckCoreContract::class,
            function (MockInterface $mock)  use ($mockException) {
                $mock->shouldReceive('getHealthiness')
                    ->once()
                    ->andThrow($mockException);
            }
        );
        $this->instance(HealthcheckCoreContract::class, $mockCore);

        MockerLoggerMessageFactory::make($this)
            ->setHTTPStart(
                'Healthcheck endpoint',
                [],
                $logInfoMessage,
            )->setHTTPError(
                $mockException,
                $logErrorMessage,
            )->bindInstance();

        Log::shouldReceive('info')
            ->withArgs(function ($argMessage) use ($logInfoMessage) {
                try {
                    $this->assertEquals($logInfoMessage, $argMessage);
                    return true;
                } catch (Exception $e) {
                    dd($e);
                }
            })->once();
        Log::shouldReceive('error')
            ->withArgs(function ($argMessage) use ($logErrorMessage) {
                try {
                    $this->assertEquals($logErrorMessage, $argMessage);
                    return true;
                } catch (Exception $e) {
                    dd($e);
                }
            })->once();


        // Act
        $response = $this->getJson(
            $this->getEndpointUrl(),
        );


        // Assert
        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);

        $exceptionMessage = new ExceptionMessageGeneric;

        $response->assertJsonPath(
            'errors',
            $exceptionMessage->getJsonResponse()->toArray()
        );
    }

    #[Test]
    #[DataProvider('healthyHealthStatusDataProvider')]
    public function should_show_200_when_all_dependencies_is_healthy(
        HealthcheckResponse $mockedHealthcheckResponse,
    ) {
        // Arrange
        $logInfoMessage = $this->faker->sentence;
        $logSuccessMessage = $this->faker->sentence;


        // Assert
        $mockCore = $this->mock(
            HealthcheckCoreContract::class,
            function (MockInterface $mock) use ($mockedHealthcheckResponse) {
                $mock->shouldReceive('getHealthiness')
                    ->once()
                    ->andReturn($mockedHealthcheckResponse);
            }
        );
        $this->instance(HealthcheckCoreContract::class, $mockCore);

        MockerLoggerMessageFactory::make($this)
            ->setHTTPStart(
                'Healthcheck endpoint',
                [],
                $logInfoMessage,
            )->setHTTPSuccess(
                'Healthcheck endpoint',
                [],
                $logSuccessMessage,
            )->bindInstance();

        Log::shouldReceive('info')
            ->withArgs(function ($argMessage) use ($logInfoMessage) {
                try {
                    $this->assertEquals($logInfoMessage, $argMessage);
                    return true;
                } catch (Exception $e) {
                    dd($e);
                }
            })->once();
        Log::shouldReceive('info')
            ->withArgs(function ($argMessage) use ($logSuccessMessage) {
                try {
                    $this->assertEquals($logSuccessMessage, $argMessage);
                    return true;
                } catch (Exception $e) {
                    dd($e);
                }
            })->once();


        // Act
        $response = $this->getJson(
            $this->getEndpointUrl(),
        );


        // Assert
        $response->assertOk();
        $response->assertExactJson($mockedHealthcheckResponse->toArray());
    }

    public static function healthyHealthStatusDataProvider(): array
    {
        return [
            'when all dependencies is healthy (1 dependency)' => [
                new HealthcheckResponse(
                    'v1.0.0',
                    new HealthcheckStatus('mysql', null),
                ),
            ],
            'when all dependencies is healthy (2 dependency)' => [
                new HealthcheckResponse(
                    'v1.0.0',
                    new HealthcheckStatus('mysql', null),
                    new HealthcheckStatus('redis', null),
                ),
            ],
        ];
    }

    #[Test]
    #[DataProvider('badHealthStatusDataProvider')]
    public function should_show_503_when_some_dependency_is_bad(
        HealthcheckResponse $mockedHealthcheckResponse,
    ) {
        // Arrange
        $logInfoMessage = $this->faker->sentence;
        $logErrorMessage = $this->faker->sentence;


        // Assert
        $mockCore = $this->mock(
            HealthcheckCoreContract::class,
            function (MockInterface $mock) use ($mockedHealthcheckResponse) {
                $mock->shouldReceive('getHealthiness')
                    ->once()
                    ->andReturn($mockedHealthcheckResponse);
            }
        );
        $this->instance(HealthcheckCoreContract::class, $mockCore);

        MockerLoggerMessageFactory::make($this)
            ->setHTTPStart(
                'Healthcheck endpoint',
                [],
                $logInfoMessage,
            )->setHTTPSuccess(
                'Healthcheck endpoint',
                [
                    'detail' => $mockedHealthcheckResponse->toArrayDetail(),
                ],
                $logErrorMessage,
            )->bindInstance();

        Log::shouldReceive('info')
            ->withArgs(function ($argMessage) use ($logInfoMessage) {
                try {
                    $this->assertEquals($logInfoMessage, $argMessage);
                    return true;
                } catch (Exception $e) {
                    dd($e);
                }
            })->once();
        Log::shouldReceive('emergency')
            ->withArgs(function ($argMessage) use ($logErrorMessage) {
                try {
                    $this->assertEquals($logErrorMessage, $argMessage);
                    return true;
                } catch (Exception $e) {
                    dd($e);
                }
            })->once();


        // Act
        $response = $this->getJson(
            $this->getEndpointUrl(),
        );


        // Assert
        $response->assertStatus(Response::HTTP_SERVICE_UNAVAILABLE);
        $response->assertExactJson($mockedHealthcheckResponse->toArray());
    }

    public static function badHealthStatusDataProvider(): array
    {
        return [
            'when one of dependencies is NOT healthy' => [
                new HealthcheckResponse(
                    'v1.0.0',
                    new HealthcheckStatus('mysql', new Exception('foo bar')),
                    new HealthcheckStatus('redis', null),
                ),
            ],
            'when all dependencies is NOT healthy' => [
                new HealthcheckResponse(
                    'v1.0.0',
                    new HealthcheckStatus('mysql', new Exception('foo bar')),
                    new HealthcheckStatus('redis', new Exception('foo bar')),
                ),
            ],
        ];
    }

    protected function getEndpointUrl(): string
    {
        return route('healthcheck');
    }
}

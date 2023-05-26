<?php

namespace Tests\Unit\Providers;

use App\Providers\CoreServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Unit\Providers\CoreBinder\CoreBinderTestCaseAbstract;
use Tests\Unit\Providers\CoreBinder\CoreBinderTestCaseFormatter;
use Tests\Unit\Providers\CoreBinder\CoreBinderTestCaseHealthcheck;
use Tests\Unit\Providers\CoreBinder\CoreBinderTestCaseLogger;
use Tests\Unit\Providers\CoreBinder\CoreBinderTestCaseUser;

class CoreServiceProviderTest extends TestCase
{
    /** @var Application|MockInterface */
    public $applicationMock;
    public CoreServiceProvider $serviceProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->applicationMock = Mockery::mock(Application::class);
        $this->serviceProvider = new CoreServiceProvider($this->applicationMock);
    }

    #[Test]
    public function should_be_able_to_be_contructed()
    {
        // Assert
        $this->assertInstanceOf(ServiceProvider::class, $this->serviceProvider);
    }

    #[Test]
    public function should_bind_core_service()
    {
        // Arrange
        $coreAssertionClassNames = [
            CoreBinderTestCaseFormatter::class,
            CoreBinderTestCaseHealthcheck::class,
            CoreBinderTestCaseLogger::class,
            CoreBinderTestCaseUser::class,
        ];


        // Assert
        foreach ($coreAssertionClassNames as $className) {
            /** @var CoreBinderTestCaseAbstract */
            $coreAssertion = new $className($this);

            $coreAssertion->assertBind();
            $coreAssertion->assertMake();
        }


        // Act
        $this->serviceProvider->boot();
    }
}

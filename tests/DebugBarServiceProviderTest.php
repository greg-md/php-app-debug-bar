<?php

namespace Greg\AppImagix;

use DebugBar\StandardDebugBar;
use Greg\AppDebugBar\DebugBarServiceProvider;
use Greg\AppInstaller\Application;
use Greg\Framework\Http\HttpKernel;
use Greg\Framework\ServiceProvider;
use Greg\Support\Dir;
use Greg\Support\Http\Response;
use PHPUnit\Framework\TestCase;

class DebugBarServiceProviderTest extends TestCase
{
    private $rootPath = __DIR__ . '/app';

    protected function setUp()
    {
        Dir::make($this->rootPath);

        Dir::make($this->rootPath . '/app');
        Dir::make($this->rootPath . '/build-deploy');
        Dir::make($this->rootPath . '/config');
        Dir::make($this->rootPath . '/public');
        Dir::make($this->rootPath . '/resources');
        Dir::make($this->rootPath . '/storage');
    }

    protected function tearDown()
    {
        Dir::unlink($this->rootPath);
    }

    public function testCanInstantiate()
    {
        $serviceProvider = new DebugBarServiceProvider();

        $this->assertInstanceOf(ServiceProvider::class, $serviceProvider);
    }

    public function testCanGetName()
    {
        $serviceProvider = new DebugBarServiceProvider();

        $this->assertEquals('greg-debug-bar', $serviceProvider->name());
    }

    public function testCanBoot()
    {
        $serviceProvider = new DebugBarServiceProvider();

        $app = new Application([
            'debug_bar' => [
                'base_url' => '/debug',
            ],
        ]);

        $app->configure($this->rootPath);

        $serviceProvider->boot($app);

        $serviceProvider->bootHttpKernel($app);

        /** @var StandardDebugBar $debugBar */
        $debugBar = $app->get(StandardDebugBar::class);

        $this->assertInstanceOf(StandardDebugBar::class, $debugBar);

        $this->assertEquals($app->config('debug_bar.base_url'), $debugBar->getJavascriptRenderer()->getBaseUrl());

        $app->fire(HttpKernel::EVENT_FINISHED, $response = new Response());

        $this->assertContains('var phpdebugbar', $response->getContent());
    }

    public function testCanInstall()
    {
        $serviceProvider = new DebugBarServiceProvider();

        $app = new Application([
            'debug_bar' => [
                'base_url' => '/debug',
            ],
        ]);

        $app->configure($this->rootPath);

        $serviceProvider->boot($app);

        $serviceProvider->install($app);

        $this->assertFileExists(__DIR__ . '/app/config/debug_bar.php');

        $this->assertDirectoryExists(__DIR__ . '/app/public/debug');
    }

    public function testCanUninstall()
    {
        $serviceProvider = new DebugBarServiceProvider();

        $app = new Application([
            'debug_bar' => [
                'base_url' => '/debug',
            ],
        ]);

        $app->configure($this->rootPath);

        $serviceProvider->boot($app);

        file_put_contents(__DIR__ . '/app/config/debug_bar.php', '');

        Dir::make(__DIR__ . '/app/public/debug');

        $serviceProvider->uninstall($app);

        $this->assertFileNotExists(__DIR__ . '/app/config/debug_bar.php');

        $this->assertDirectoryNotExists(__DIR__ . '/app/public/debug');
    }
}

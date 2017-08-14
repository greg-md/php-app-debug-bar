<?php

namespace Greg\AppDebugBar;

use DebugBar\StandardDebugBar;
use Greg\AppInstaller\Application;
use Greg\AppInstaller\Events\ConfigAddEvent;
use Greg\AppInstaller\Events\ConfigRemoveEvent;
use Greg\AppInstaller\Events\PublicAddEvent;
use Greg\AppInstaller\Events\PublicRemoveEvent;
use Greg\Framework\Http\HttpKernel;
use Greg\Framework\ServiceProvider;
use Greg\Support\Http\Request;
use Greg\Support\Http\Response;

class DebugBarServiceProvider implements ServiceProvider
{
    private const CONFIG_NAME = 'debug_bar';

    private $app;

    public function name(): string
    {
        return 'greg-debug-bar';
    }

    public function boot(Application $app)
    {
        $this->app = $app;
    }

    public function bootHttpKernel(Application $app)
    {
        $app->inject(StandardDebugBar::class, function () {
            $debugBar = new StandardDebugBar();

            $debugBar->getJavascriptRenderer($this->config('base_url'));

            return $debugBar;
        });

        $app->listen(HttpKernel::EVENT_FINISHED, function (Response $response) {
            if (!Request::isAjax() and $response->isHtml()) {
                $renderer = $this->debugBar()->getJavascriptRenderer();

                $response->setContent(
                    $response->getContent() . '<span></span>' . $renderer->renderHead() . $renderer->render()
                );
            }
        });
    }

    public function install(Application $app)
    {
        $app->event(new ConfigAddEvent(__DIR__ . '/../config/config.php', self::CONFIG_NAME));

        $resourcesPath = 'vendor/maximebf/debugbar/src/DebugBar/Resources';

        $app->event(new PublicAddEvent($resourcesPath, $this->config('base_url')));
    }

    public function uninstall(Application $app)
    {
        $app->event(new PublicRemoveEvent($this->config('base_url')));

        $app->event(new ConfigRemoveEvent(self::CONFIG_NAME));
    }

    private function debugBar(): StandardDebugBar
    {
        return $this->app()->get(StandardDebugBar::class);
    }

    private function config($name)
    {
        return $this->app()->config(self::CONFIG_NAME . '.' . $name);
    }

    private function app(): Application
    {
        return $this->app;
    }
}

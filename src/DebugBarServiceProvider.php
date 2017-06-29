<?php

namespace Greg\AppDebugBar;

use DebugBar\StandardDebugBar;
use Greg\Framework\Application;
use Greg\Framework\Http\HttpKernel;
use Greg\Framework\ServiceProvider;
use Greg\Support\Http\Request;
use Greg\Support\Http\Response;

class DebugBarServiceProvider implements ServiceProvider
{
    private const CONFIG_NAME = 'debugBar';

    private $app;

    public function name(): string
    {
        return 'greg-debug-bar';
    }

    public function boot(Application $app)
    {
        $this->app = $app;
    }

    public function bootHttpKernel()
    {
        $this->app()->inject(StandardDebugBar::class, function () {
            $debugBar = new StandardDebugBar();

            $debugBar->getJavascriptRenderer($this->config('base_url'));

            return $debugBar;
        });

        $this->app()->listen(HttpKernel::EVENT_FINISHED, function (Response $response) {
            if (!$this->config('disabled') and !Request::isAjax() and $response->isHtml()) {
                $renderer = $this->debugBar()->getJavascriptRenderer();

                $response->setContent(
                    $response->getContent() . '<span></span>' . $renderer->renderHead() . $renderer->render()
                );
            }
        });
    }

    public function install()
    {
        $this->app()->fire('app.config.add', __DIR__ . '/../config/config.php', self::CONFIG_NAME);

        $resourcesPath = getcwd() . '/vendor/maximebf/debugbar/src/DebugBar/Resources';

        $this->app()->fire('app.public.add', $resourcesPath, $this->config('base_url'));
    }

    public function uninstall()
    {
        $this->app()->fire('app.config.remove', self::CONFIG_NAME);

        $this->app()->fire('app.public.remove', $this->config('base_url'));
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

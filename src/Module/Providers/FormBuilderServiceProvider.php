<?php

namespace RefinedDigital\FormBuilder\Module\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use RefinedDigital\FormBuilder\Commands\Install;
use RefinedDigital\CMS\Modules\Core\Aggregates\PublicRouteAggregate;
use RefinedDigital\CMS\Modules\Core\Aggregates\PackageAggregate;
use RefinedDigital\CMS\Modules\Core\Aggregates\ModuleAggregate;
use RefinedDigital\CMS\Modules\Core\Aggregates\RouteAggregate;

class FormBuilderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        view()->addNamespace('formBuilder', [
            base_path('resources/views/forms'),
            app_path('RefinedCMS/Forms'),
            __DIR__.'/../Resources/views',
        ]);

        try {
            if ($this->app->runningInConsole()) {
                if (\DB::connection()->getDatabaseName() && !\Schema::hasTable('forms')) {
                    $this->commands([
                        Install::class
                    ]);
                }
            }
        } catch (\Exception $e) {}

        $this->publishes([
            __DIR__.'/../../../config/form-builder.php' => config_path('form-builder.php'),
        ], 'formBuilder');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        app(RouteAggregate::class)
            ->addRouteFile('formBuilder', __DIR__.'/../Http/routes.php');
        app(PublicRouteAggregate::class)
            ->addRouteFile('formBuilder', __DIR__.'/../Http/public-routes.php');

        $this->mergeConfigFrom(__DIR__.'/../../../config/form-builder.php', 'form-builder');

        $menuConfig = [
            'order' => 400,
            'name' => 'Form Builder',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColour"><path class="fa-secondary" opacity=".4" d="M160 96c0-17.7 14.3-32 32-32H480c17.7 0 32 14.3 32 32s-14.3 32-32 32H192c-17.7 0-32-14.3-32-32zm0 160c0-17.7 14.3-32 32-32H480c17.7 0 32 14.3 32 32s-14.3 32-32 32H192c-17.7 0-32-14.3-32-32zm32 128H480c17.7 0 32 14.3 32 32s-14.3 32-32 32H192c-17.7 0-32-14.3-32-32s14.3-32 32-32z"/><path class="fa-primary" d="M16 72c0-13.3 10.7-24 24-24H88c13.3 0 24 10.7 24 24v48c0 13.3-10.7 24-24 24H40c-13.3 0-24-10.7-24-24V72zm0 160c0-13.3 10.7-24 24-24H88c13.3 0 24 10.7 24 24v48c0 13.3-10.7 24-24 24H40c-13.3 0-24-10.7-24-24V232zM40 368H88c13.3 0 24 10.7 24 24v48c0 13.3-10.7 24-24 24H40c-13.3 0-24-10.7-24-24V392c0-13.3 10.7-24 24-24z"/></svg>',
            'route' => 'form-builder',
            'activeFor' => ['form-builder']
        ];

        app(ModuleAggregate::class)
            ->addMenuItem($menuConfig);

        app(PackageAggregate::class)
            ->addPackage('FormBuilder', [
                'repository' => \RefinedDigital\FormBuilder\Module\Http\Repositories\FormBuilderRepository::class,
                'model' => '\\RefinedDigital\\FormBuilder\\Module\\Models\\FormBuilder',
            ])
        ;

        // load in the extra packages that do not get auto discovered
        $loader = AliasLoader::getInstance();
        $this->app->register(\Msurguy\Honeypot\HoneypotServiceProvider::class);
        $loader->alias('Honeypot', \Msurguy\Honeypot\HoneypotFacade::class);
    }
}

<?php

namespace RefinedDigital\FormBuilder\Module\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use RefinedDigital\CMS\Modules\Core\Models\PublicRouteAggregate;
use RefinedDigital\FormBuilder\Commands\Install;
use RefinedDigital\CMS\Modules\Core\Models\PackageAggregate;
use RefinedDigital\CMS\Modules\Core\Models\ModuleAggregate;
use RefinedDigital\CMS\Modules\Core\Models\RouteAggregate;

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
            __DIR__.'/../Resources/views',
            app_path().'/RefinedCMS/Forms',
            base_path().'/resources/views/forms'
        ]);

        if ($this->app->runningInConsole()) {
            if (\DB::connection()->getDatabaseName() && !\Schema::hasTable('forms')) {
                $this->commands([
                    Install::class
                ]);
            }
        }

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
            'icon' => 'fas fa-list',
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

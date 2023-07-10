<?php

namespace App\Admin\Providers;

use Illuminate\Routing\Router;
use SleepingOwl\Admin\Contracts\Navigation\NavigationInterface;
use SleepingOwl\Admin\Contracts\Template\MetaInterface;
use SleepingOwl\Admin\Contracts\Widgets\WidgetsRegistryInterface;
use SleepingOwl\Admin\Providers\AdminSectionsServiceProvider as ServiceProvider;

class AdminSectionsServiceProvider extends ServiceProvider
{
    /**
     * Список виджетов, которые необходимо подключить в шаблоны
     * @var array
     */
    protected $widgets = [
        \App\Admin\Widgets\NavigationUserBlock::class,
    ];

    /**
     * @var array
     */
    protected $sections = [
        // alphabet order
        //priority by use
        //\App\User::class => 'App\Http\Sections\Users',
        \App\Models\Invoice::class => 'App\Http\Sections\Invoices'
    ];

    /**
     * Register sections.
     *
     * @param \SleepingOwl\Admin\Admin $admin
     * @return void
     */
    public function boot(\SleepingOwl\Admin\Admin $admin)
    {
        $this->loadViewsFrom( base_path( "app/admin/resources/views" ), 'admin' );
//        $this->registerPolicies( 'Admin\\Policies\\' );

        $this->app->call( [ $this, 'registerRoutes' ] );
        $this->app->call( [ $this, 'registerNavigation' ] );

        parent::boot($admin);

        $this->app->call( [ $this, 'registerViews' ] );
        $this->app->call( [ $this, 'registerMediaPackages' ] );
    }

    /**
     * @param NavigationInterface $navigation
     */
    public function registerNavigation( NavigationInterface $navigation ) {

        require base_path( 'app/admin/navigation.php' );
    }

    /**
     * @param WidgetsRegistryInterface $widgetsRegistry
     */
    public function registerViews( WidgetsRegistryInterface $widgetsRegistry ) {
        foreach ( $this->widgets as $widget ) {
            $widgetsRegistry->registerWidget( $widget );
        }
    }

    /**
     * @param Router $router
     */
    public function registerRoutes( Router $router ) {
        $router->group( [
            'prefix'     => config( 'sleeping_owl.url_prefix' ),
            'middleware' => config( 'sleeping_owl.middleware' )
        ], function ( $router ) {
            require base_path( 'app/admin/http/routes.php' );
        } );
    }

    /**
     * @param MetaInterface $meta
     */
    public function registerMediaPackages( MetaInterface $meta ) {
        $packages = $meta->assets()->packageManager();

        require base_path( 'app/admin/assets.php' );
    }
}

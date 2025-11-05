<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Order;
use App\Models\ContactForm;
use App\Observers\OrderObserver;
use App\Observers\ContactFormObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar observers
        Order::observe(OrderObserver::class);
        ContactForm::observe(ContactFormObserver::class);
    }
}

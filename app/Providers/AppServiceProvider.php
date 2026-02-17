<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Resources\Asset;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Asset::withoutWrapping();
    }
}

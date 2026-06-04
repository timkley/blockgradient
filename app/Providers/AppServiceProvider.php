<?php

namespace App\Providers;

use App\Models\Block;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('blocks', function () {
            $blocks = cache()->remember('blocks:attributes', now()->addDay(), function () {
                return Block::query()
                    ->get()
                    ->map->getAttributes()
                    ->all();
            });

            return Block::hydrate($blocks);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
    }
}

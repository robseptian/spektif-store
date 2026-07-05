<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Plan;
use App\Observers\UserObserver;
use App\Observers\PlanObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\WebhookService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureMediaLibrary();

        // Register the UserObserver
        User::observe(UserObserver::class);
        
        // Register the PlanObserver
        Plan::observe(PlanObserver::class);
        


        // Configure dynamic storage disks
        try {
            \App\Services\DynamicStorageService::configureDynamicDisks();
        } catch (\Exception $e) {
            // Silently fail during migrations or when database is not ready
        }
    }

    private function configureMediaLibrary(): void
    {
        $requested = filter_var(env('MEDIA_ENABLE_IMAGE_OPTIMIZATION', false), FILTER_VALIDATE_BOOLEAN);
        $shellAvailable = $this->shellFunctionsAvailable();

        if (! $requested || ! $shellAvailable) {
            config([
                'media-library.enable_image_optimization' => false,
                'media-library.image_optimizers' => [],
            ]);

            return;
        }

        config(['media-library.enable_image_optimization' => true]);
    }

    private function shellFunctionsAvailable(): bool
    {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        foreach (['escapeshellarg', 'exec'] as $function) {
            if (! function_exists($function) || in_array($function, $disabled, true)) {
                return false;
            }
        }

        return true;
    }
}
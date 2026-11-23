<?php

namespace App\Providers;

use App\Services\Ai\AiClient;
use App\Services\Ai\GeminiClient;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiClient::class, fn (): AiClient => match (config('ai.default')) {
            'gemini' => GeminiClient::fromConfig(),
            default => throw new InvalidArgumentException('AI_PROVIDER tidak dikenal. Nilai yang didukung: gemini.'),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

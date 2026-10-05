<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        $this->configureRateLimiters();
    }

    /**
     * Batasi laju request API sesuai PRD 58 (rate limiting).
     *
     * `api` dipasang otomatis oleh throttleApi() sebagai jaring pengaman umum,
     * limiter bernama di bawah ini mengatur batas yang lebih ketat per jenis endpoint.
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(300)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('api-public', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('api-auth', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('api-write', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('realtime', fn (Request $request) => Limit::perMinute((int) config('marketplace.realtime.limit_per_minute', 120))->by($request->user()?->id ?: $request->ip()));

        // Generate deskripsi AI memakai kuota API berbayar, jadi limiter-nya lebih ketat.
        RateLimiter::for('ai-description', fn (Request $request) => Limit::perMinute((int) config('marketplace.ai.limit_per_minute', 6))->by($request->user()?->id ?: $request->ip()));

        // Endpoint web login/register tidak pernah memakai limiter API, sehingga
        // tanpa ini login bisa ditebak-tebak tanpa batas (credential stuffing).
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by('login-ip:'.$request->ip()),
            Limit::perMinute(10)->by('login-email:'.mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
        ]);

        RateLimiter::for('register', fn (Request $request) => [
            Limit::perMinute(3)->by('register-ip:'.$request->ip()),
            Limit::perMinute(20)->by('register-global'),
        ]);
    }
}

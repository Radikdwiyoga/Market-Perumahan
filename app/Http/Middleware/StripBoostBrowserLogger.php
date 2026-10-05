<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hapus script Boost browser logger dari response HTML.
 *
 * Boost MCP server inject script ke semua HTML response untuk capture
 * console.log/error ke endpoint `/_boost/browser-logs`. Script ini
 * bikin error di ngrok free tier (halaman warning blokir POST).
 */
class StripBoostBrowserLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Strip bila:
        // - non-local environment, ATAU
        // - env var STRIP_BOOST_LOGGER=true, ATAU
        // - request datang via ngrok (header/host)
        $shouldStrip = ! app()->environment('local')
            || (bool) env('STRIP_BOOST_LOGGER', false)
            || $this->isNgrokRequest($request);

        if ($shouldStrip && $this->isHtml($response)) {
            $content = $response->getContent();
            $content = preg_replace(
                '/<script[^>]*>[\s\S]*?_boost[\/]browser-logs[\s\S]*?<\/script>/i',
                '',
                $content
            );
            $response->setContent($content);
        }

        return $response;
    }

    private function isHtml(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        return str_contains($contentType, 'text/html');
    }

    private function isNgrokRequest(Request $request): bool
    {
        $host = $request->getHost();
        return str_ends_with($host, '.ngrok-free.dev')
            || str_ends_with($host, '.ngrok.io')
            || $request->hasHeader('ngrok-skip-browser-warning');
    }
}
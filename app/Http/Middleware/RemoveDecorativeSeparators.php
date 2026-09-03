<?php

namespace App\Http\Middleware;

use App\Support\DecorativeSeparatorCleaner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemoveDecorativeSeparators
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $contentType = (string) $response->headers->get('Content-Type');
        if (!str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            return $response;
        }

        $response->setContent(DecorativeSeparatorCleaner::clean($content));

        return $response;
    }
}

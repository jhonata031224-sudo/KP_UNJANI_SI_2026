<?php

namespace App\Http\Middleware;

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

        $protected = [];
        $content = preg_replace_callback(
            '~<(script|style)\b[^>]*>.*?</\1>~is',
            function (array $match) use (&$protected): string {
                $key = '___DECORATIVE_SEPARATOR_BLOCK_'.count($protected).'___';
                $protected[$key] = $match[0];
                return $key;
            },
            $content
        );

        $content = preg_replace_callback(
            '~>([^<]+)<~',
            static function (array $match): string {
                $text = $match[1];
                $text = preg_replace('/\s*\/\/\s*/u', ' ', $text);
                $text = preg_replace('/(^|\s)[-—](?=\s|$)/u', '$1', $text);
                $text = preg_replace('/ {2,}/', ' ', $text);

                return '>'.$text.'<';
            },
            $content
        );

        foreach ($protected as $key => $block) {
            $content = str_replace($key, $block, $content);
        }

        $response->setContent($content);

        return $response;
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAuthenticatedActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $user = $request->user();

        if (!$user || !$request->route()) {
            return $response;
        }

        $method = strtoupper($request->method());
        $route = $request->route();
        $routeName = $route->getName() ?: '-';
        $uri = '/'.ltrim($route->uri(), '/');
        $actionName = $route->getActionName();
        $action = $this->actionFor($method, $uri, $routeName);
        $context = [
            'method' => $method,
            'route' => $uri,
            'route_name' => $routeName,
            'controller' => $actionName,
            'status' => $response->getStatusCode(),
            'role' => $user->jabatan ?: '-',
            'satuan' => $user->satuan?->nama ?: '-',
            'username' => $user->username ?: '-',
        ];

        ActivityLog::catat(
            $action,
            $this->description($action, $routeName, $uri, $method, $response->getStatusCode()),
            $user,
            $context,
        );

        return $response;
    }

    private function actionFor(string $method, string $uri, string $routeName): string
    {
        $target = strtolower($routeName.' '.$uri);

        if (str_contains($target, 'export') || str_contains($target, 'download')) {
            return 'export';
        }

        return match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            'GET', 'HEAD' => 'view',
            default => strtolower($method),
        };
    }

    private function description(string $action, string $routeName, string $uri, string $method, int $status): string
    {
        $verb = match ($action) {
            'create' => 'Menambahkan/mengirim data',
            'update' => 'Mengubah data',
            'delete' => 'Menghapus data',
            'export' => 'Mengekspor/mengunduh data',
            'view' => 'Membuka/melihat halaman atau data',
            default => 'Menjalankan aktivitas',
        };

        return sprintf('%s pada %s (%s) · route: %s · status: %d', $verb, $uri, $method, $routeName, $status);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GenerateOpenApi extends Command
{
    protected $signature = 'docs:openapi {--out=../docs/openapi.json}';

    protected $description = 'Sinh tài liệu OpenAPI 3 (JSON) từ danh sách route /api/v1';

    public function handle(): int
    {
        $paths = [];
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }
            $path = '/'.preg_replace('#^api/v1/#', '', $route->uri());
            $path = preg_replace('/\{(\w+)\??\}/', '{$1}', $path);
            $mw = $route->gatherMiddleware();
            $auth = collect($mw)->first(fn ($m) => is_string($m) && str_starts_with($m, 'auth:'));
            $perms = collect($mw)->filter(fn ($m) => is_string($m) && str_starts_with($m, 'perm:'))->map(fn ($m) => substr($m, 5))->values()->all();
            preg_match_all('/\{(\w+)\??\}/', $route->uri(), $m);
            $params = array_map(fn ($n) => ['name' => $n, 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']], $m[1]);
            $tag = explode('/', trim($path, '/'))[0] === 'admin' ? 'admin/'.(explode('/', trim($path, '/'))[1] ?? '') : explode('/', trim($path, '/'))[0];
            foreach (array_diff($route->methods(), ['HEAD', 'OPTIONS']) as $method) {
                $op = [
                    'tags' => [$tag], 'summary' => Str::headline(class_basename(explode('@', $route->getActionName())[0])).'::'.(explode('@', $route->getActionName())[1] ?? ''),
                    'responses' => ['200' => ['description' => 'OK'], '422' => ['description' => 'Lỗi validate { error: { code, message, details } }']],
                ];
                if ($params) {
                    $op['parameters'] = $params;
                }
                if ($auth) {
                    $op['security'] = [['bearerAuth' => []]];
                    $op['responses']['401'] = ['description' => 'TOKEN_EXPIRED | TOKEN_INVALID | SESSION_REVOKED'];
                }
                if ($perms) {
                    $op['description'] = 'Quyền cần có: '.implode(' hoặc ', $perms);
                    $op['responses']['403'] = ['description' => 'FORBIDDEN'];
                }
                $paths[$path][strtolower($method)] = $op;
            }
        }
        ksort($paths);
        $doc = [
            'openapi' => '3.0.3',
            'info' => ['title' => 'SoleHouse API', 'version' => '1.0.0', 'description' => 'REST JSON. Auth: Bearer access token (JWT); refresh token ở cookie HttpOnly. Tiền: số nguyên VND.'],
            'servers' => [['url' => rtrim(config('app.url'), '/').'/api/v1']],
            'components' => ['securitySchemes' => ['bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT']]],
            'paths' => $paths,
        ];
        file_put_contents($this->option('out'), json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->info(count($paths).' đường dẫn → '.$this->option('out'));

        return self::SUCCESS;
    }
}

<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Services\ImageStore;
use App\Support\ApiException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * CRUD dùng chung cho các danh mục đơn giản (brands, categories, colors, sizes, coupons, promotions, banners, pages, shipping-rates).
 * Cấu hình theo từng resource trong ResourceRegistry.
 */
class AdminResourceController
{
    public function __construct(private ImageStore $images) {}

    private function cfg(Request $r): array
    {
        $name = Route::current()->defaults['resource'];

        return ResourceRegistry::get($name);
    }

    private function flush(array $c): void
    {
        if ($c['flush'] ?? false) {
            Cache::tags(['catalog', 'home', 'settings'])->flush();
        }
    }

    private function present(array $c, Model $m): array
    {
        $a = $m->toArray();
        foreach ($c['images'] ?? [] as $col => $key) {
            $a[$key] = storage_url($m->{$col});
        }
        if (isset($c['present'])) {
            $a = ($c['present'])($m, $a);
        }

        return $a;
    }

    public function index(Request $r)
    {
        $c = $this->cfg($r);
        $q = ($c['model'])::query();
        if (isset($c['with'])) {
            $q->with($c['with']);
        }
        if (($s = trim((string) $r->query('q'))) && isset($c['search'])) {
            $q->where(fn ($w) => collect($c['search'])->each(fn ($col) => $w->orWhere($col, 'like', "%$s%")));
        }
        foreach ($c['filters'] ?? [] as $f) {
            if ($r->filled($f)) {
                $q->where($f, $r->query($f));
            }
        }
        foreach ($c['order'] ?? [['id', 'desc']] as [$col, $dir]) {
            $q->orderBy($col, $dir);
        }
        if ($r->boolean('all')) {
            return ['data' => $q->get()->map(fn ($m) => $this->present($c, $m))->values()];
        }
        $page = $q->paginate(min(100, (int) $r->query('limit', 20)));

        return ['data' => $page->getCollection()->map(fn ($m) => $this->present($c, $m))->values(),
            'meta' => ['page' => $page->currentPage(), 'limit' => $page->perPage(), 'total' => $page->total()]];
    }

    public function show(Request $r, int $id)
    {
        $c = $this->cfg($r);

        return ['data' => $this->present($c, ($c['model'])::findOrFail($id))];
    }

    private function prepare(array $c, array $data, ?Model $existing): array
    {
        if (($c['slug'] ?? false) && empty($data['slug']) && ! empty($data['name'])) {
            $base = Str::slug($data['name']);
            $slug = $base;
            $i = 2;
            while (($c['model'])::where('slug', $slug)->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists()) {
                $slug = $base.'-'.$i++;
            }
            $data['slug'] = $slug;
        }
        if (isset($c['upper'])) {
            foreach ($c['upper'] as $f) {
                if (isset($data[$f])) {
                    $data[$f] = strtoupper(trim($data[$f]));
                }
            }
        }
        foreach ($c['images'] ?? [] as $col => $key) {
            if (array_key_exists($col, $data)) {
                $new = $this->images->commit($data[$col], $c['dir']);
                if ($existing && $existing->{$col} && $existing->{$col} !== $new) {
                    $this->images->delete($existing->{$col});
                }
                $data[$col] = $new;
            }
        }

        foreach ($c['sanitize'] ?? [] as $f) {
            if (isset($data[$f])) {
                $data[$f] = \App\Modules\Catalog\Services\ProductService::sanitizeHtml($data[$f]);
            }
        }
        foreach ($c['strip'] ?? [] as $f) {
            unset($data[$f]);
        }

        return $data;
    }

    public function store(Request $r)
    {
        $c = $this->cfg($r);
        $data = $this->prepare($c, $r->validate(($c['rules'])(null)), null);
        $m = ($c['model'])::create($data);
        $this->after($c, $m, $r);
        $this->flush($c);

        return response()->json(['data' => $this->present($c, $m->refresh())], 201);
    }

    public function update(Request $r, int $id)
    {
        $c = $this->cfg($r);
        $m = ($c['model'])::findOrFail($id);
        $m->update($this->prepare($c, $r->validate(($c['rules'])($m)), $m));
        $this->after($c, $m, $r);
        $this->flush($c);

        return ['data' => $this->present($c, $m->refresh())];
    }

    public function destroy(Request $r, int $id)
    {
        $c = $this->cfg($r);
        $m = ($c['model'])::findOrFail($id);
        if (isset($c['guard'])) {
            ($c['guard'])($m);
        }
        try {
            $m->delete();
        } catch (\Illuminate\Database\QueryException) {
            throw ApiException::conflict('IN_USE', 'Mục này đang được sử dụng, không thể xóa');
        }
        foreach ($c['images'] ?? [] as $col => $_) {
            $this->images->delete($m->{$col});
        }
        $this->flush($c);

        return response()->noContent();
    }

    private function after(array $c, Model $m, Request $r): void
    {
        if (isset($c['after'])) {
            ($c['after'])($m, $r);
        }
    }
}

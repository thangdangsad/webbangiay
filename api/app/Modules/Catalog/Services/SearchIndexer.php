<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Meilisearch qua REST (không cần package). Chỉ hoạt động khi SEARCH_DRIVER=meilisearch; lỗi không làm hỏng luồng chính. */
class SearchIndexer
{
    public static function enabled(): bool
    {
        return config('shoe.search.driver') === 'meilisearch';
    }

    private function http()
    {
        return Http::baseUrl(rtrim(config('shoe.search.host'), '/'))->withToken(config('shoe.search.key'))->timeout(5);
    }

    public function document(Product $p): array
    {
        return ['id' => $p->id, 'name' => $p->name, 'brand' => $p->brand?->name, 'category' => $p->category?->name, 'slug' => $p->slug];
    }

    public function setup(): void
    {
        $idx = config('shoe.search.index');
        $this->http()->post('/indexes', ['uid' => $idx, 'primaryKey' => 'id']);
        $this->http()->patch("/indexes/$idx/settings", ['searchableAttributes' => ['name', 'brand', 'category'], 'typoTolerance' => ['enabled' => true]]);
    }

    public function reindexAll(): int
    {
        $this->setup();
        $n = 0;
        Product::with('brand:id,name', 'category:id,name')->where('status', 'active')->chunk(200, function ($chunk) use (&$n) {
            $this->http()->post('/indexes/'.config('shoe.search.index').'/documents', $chunk->map(fn ($p) => $this->document($p))->values()->all());
            $n += $chunk->count();
        });

        return $n;
    }

    public function sync(Product $p): void
    {
        if (! self::enabled()) {
            return;
        }
        try {
            $idx = config('shoe.search.index');
            if ($p->status === 'active' && ! $p->trashed()) {
                $this->http()->post("/indexes/$idx/documents", [$this->document($p->loadMissing('brand:id,name', 'category:id,name'))]);
            } else {
                $this->http()->delete("/indexes/$idx/documents/{$p->id}");
            }
        } catch (\Throwable $e) {
            Log::warning('Meilisearch sync failed', ['error' => $e->getMessage()]);
        }
    }

    /** @return int[]|null null nếu Meilisearch lỗi (caller fallback về MySQL) */
    public function search(string $term, int $limit = 1000): ?array
    {
        try {
            $res = $this->http()->post('/indexes/'.config('shoe.search.index').'/search', ['q' => $term, 'limit' => $limit, 'attributesToRetrieve' => ['id']]);

            return $res->successful() ? collect($res->json('hits'))->pluck('id')->all() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

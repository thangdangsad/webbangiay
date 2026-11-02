<?php

namespace App\Console\Commands;

use App\Modules\Catalog\Services\SearchIndexer;
use Illuminate\Console\Command;

class ReindexSearch extends Command
{
    protected $signature = 'search:reindex';

    protected $description = 'Đánh chỉ mục lại toàn bộ sản phẩm đang bán vào Meilisearch';

    public function handle(SearchIndexer $indexer): int
    {
        $this->info($indexer->reindexAll().' sản phẩm đã đánh chỉ mục.');

        return self::SUCCESS;
    }
}

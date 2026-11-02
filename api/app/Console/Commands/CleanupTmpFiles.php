<?php

namespace App\Console\Commands;

use App\Modules\Catalog\Services\ImageStore;
use Illuminate\Console\Command;

class CleanupTmpFiles extends Command
{
    protected $signature = 'files:cleanup-tmp';

    protected $description = 'Xóa tệp tải lên ở tmp/ quá 24 giờ';

    public function handle(ImageStore $images): int
    {
        $this->info('Đã xóa '.$images->cleanupTmp().' tệp.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Modules\Catalog\Services;

use App\Support\ApiException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/** Upload → WebP chuẩn hóa (tối đa 1600px) + biến thể _md (800) / _sm (300). Tệp mới nằm ở tmp/ cho đến khi gắn vào bản ghi. */
class ImageStore
{
    private const SIZES = ['md' => 800, 'sm' => 300];

    public function storeTmp(UploadedFile $file, string $type): array
    {
        $mgr = new ImageManager(new Driver());
        try {
            $img = $mgr->read($file->getRealPath())->orient();
        } catch (\Throwable) {
            throw ApiException::invalid('Tệp không phải ảnh hợp lệ');
        }
        if ($type === 'product' && ($img->width() < 400 || $img->height() < 400)) {
            throw ApiException::invalid('Ảnh sản phẩm tối thiểu 400×400 px');
        }
        $uuid = (string) Str::uuid();
        $disk = Storage::disk('public');
        $ext = $type === 'logo' && $file->getClientOriginalExtension() === 'png' ? 'png' : 'webp';
        $path = "tmp/$uuid.$ext";

        $main = $img->scaleDown(1600, 1600);
        $disk->put($path, $ext === 'png' ? (string) $main->toPng() : (string) $main->toWebp(85));
        $meta = ['width' => $main->width(), 'height' => $main->height(), 'size_bytes' => $disk->size($path)];
        if ($type === 'product' || $type === 'banner') {
            foreach (self::SIZES as $suffix => $px) {
                $disk->put(image_variant($path, $suffix), (string) $mgr->read($file->getRealPath())->orient()->scaleDown($px, $px)->toWebp(82));
            }
        }

        return ['path' => $path, 'url' => Storage::url($path)] + $meta;
    }

    /** Chuyển tệp tmp/ vào thư mục đích (kèm các biến thể); path đã ở nơi khác thì giữ nguyên. */
    public function commit(?string $path, string $dir): ?string
    {
        if (! $path || ! str_starts_with($path, 'tmp/')) {
            return $path;
        }
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            throw ApiException::invalid('Tệp ảnh tạm không còn tồn tại, vui lòng tải lại');
        }
        $dest = trim($dir, '/').'/'.basename($path);
        $disk->move($path, $dest);
        foreach (array_keys(self::SIZES) as $suffix) {
            if ($disk->exists(image_variant($path, $suffix))) {
                $disk->move(image_variant($path, $suffix), image_variant($dest, $suffix));
            }
        }

        return $dest;
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }
        $disk = Storage::disk('public');
        $disk->delete([$path, image_variant($path, 'md'), image_variant($path, 'sm')]);
    }

    public function cleanupTmp(int $hours = 24): int
    {
        $disk = Storage::disk('public');
        $n = 0;
        foreach ($disk->files('tmp') as $f) {
            if ($disk->lastModified($f) < now()->subHours($hours)->getTimestamp()) {
                $disk->delete($f);
                $n++;
            }
        }

        return $n;
    }
}

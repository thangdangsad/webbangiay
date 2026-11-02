<?php

use Illuminate\Support\Facades\Storage;

if (! function_exists('storage_url')) {
    /** Đường dẫn tương đối trong disk `public` → URL đầy đủ (DB chỉ lưu path). */
    function storage_url(?string $path): ?string
    {
        return $path ? Storage::url($path) : null;
    }
}

if (! function_exists('image_variant')) {
    /** products/1/abc.webp → products/1/abc_md.webp */
    function image_variant(?string $path, string $suffix): ?string
    {
        if (! $path) {
            return null;
        }

        return preg_replace('/\.(\w+)$/', "_{$suffix}.$1", $path);
    }
}

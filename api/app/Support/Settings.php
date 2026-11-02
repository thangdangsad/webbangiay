<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Cấu hình cửa hàng lưu bảng `settings` (key → JSON), cache Redis. */
class Settings
{
    public const DEFAULTS = [
        'store' => ['name' => 'SoleHouse', 'phone' => '1900 0000', 'email' => 'support@solehouse.vn', 'address' => '12 Trần Duy Hưng, Cầu Giấy, Hà Nội', 'logo_path' => null, 'favicon_path' => null],
        'payment' => ['cod_enabled' => true, 'momo_enabled' => true, 'momo_max_amount' => 50000000],
        'shipping' => ['free_threshold' => 1000000, 'default_fee' => 30000, 'carrier' => 'Giao hàng nhanh'],
        'order' => ['return_days' => 7, 'momo_expire_minutes' => 15, 'allow_customer_cancel_until' => 'confirmed'],
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        [$group, $field] = array_pad(explode('.', $key, 2), 2, null);
        $all = Cache::tags(['settings'])->remember("settings:$group", 300, function () use ($group) {
            $row = DB::table('settings')->where('key', $group)->value('value');

            return array_merge(self::DEFAULTS[$group] ?? [], $row ? json_decode($row, true) : []);
        });

        return $field === null ? $all : ($all[$field] ?? $default);
    }

    public static function put(string $group, array $values): void
    {
        $current = self::get($group);
        DB::table('settings')->updateOrInsert(['key' => $group], [
            'value' => json_encode(array_merge($current, $values), JSON_UNESCAPED_UNICODE), 'updated_at' => now(), 'created_at' => now(),
        ]);
        Cache::tags(['settings'])->flush();
    }

    public static function groups(): array
    {
        return array_keys(self::DEFAULTS);
    }
}

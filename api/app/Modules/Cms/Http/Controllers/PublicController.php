<?php

namespace App\Modules\Cms\Http\Controllers;

use App\Modules\Cms\Models\CmsPage;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicController
{
    public function page(string $slug)
    {
        $p = CmsPage::where('slug', $slug)->where('is_published', true)->firstOrFail();

        return ['data' => ['slug' => $p->slug, 'title' => $p->title, 'content' => $p->content, 'updated_at' => $p->updated_at]];
    }

    public function pages()
    {
        return ['data' => CmsPage::where('is_published', true)->get(['slug', 'title'])];
    }

    public function storeInfo()
    {
        return ['data' => Settings::get('store') + ['free_ship_threshold' => (int) Settings::get('shipping.free_threshold'), 'return_days' => (int) Settings::get('order.return_days'), 'google_client_id' => config('shoe.google.client_id')]];
    }

    public function contact(Request $r)
    {
        $v = $r->validate(['name' => 'required|string|max:120', 'email' => 'required|email', 'phone' => 'nullable|string|max:20', 'message' => 'required|string|max:3000']);
        Log::channel('single')->info('[contact]', $v);
        \App\Modules\Notification\Notifier::mail(Settings::get('store.email'), 'Liên hệ mới từ '.$v['name'], $v['message']."\n\n{$v['email']} {$v['phone']}");

        return response()->json(['data' => ['ok' => true]], 201);
    }

    public function newsletter(Request $r)
    {
        $v = $r->validate(['email' => 'required|email']);
        Log::info('[newsletter]', $v);

        return response()->json(['data' => ['ok' => true]], 201);
    }

    public function locations(Request $r, ?string $province = null, ?string $district = null)
    {
        $all = cache()->rememberForever('locations', fn () => json_decode(file_get_contents(database_path('data/locations.json')), true));
        if (! $province) {
            return ['data' => array_map(fn ($p) => ['code' => $p['code'], 'name' => $p['name']], $all)];
        }
        $p = collect($all)->firstWhere('code', $province) ?? abort(404);
        if (! $district) {
            return ['data' => array_map(fn ($d) => ['code' => $d['code'], 'name' => $d['name']], $p['districts'])];
        }
        $d = collect($p['districts'])->firstWhere('code', $district) ?? abort(404);

        return ['data' => $d['wards']];
    }
}

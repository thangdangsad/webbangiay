<?php

namespace App\Modules\User\Http\Controllers;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\ProductPresenter;
use App\Modules\Notification\Notifier;
use App\Modules\User\Models\Address;
use App\Support\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeController
{
    private function rules(): array
    {
        return [
            'receiver_name' => 'required|string|max:120', 'phone' => ['required', 'regex:/^(0|\+84)\d{9}$/'],
            'province_code' => 'required|string|max:10', 'district_code' => 'required|string|max:10', 'ward_code' => 'required|string|max:10',
            'street' => 'required|string|max:255', 'is_default' => 'boolean',
        ];
    }

    public function addresses(Request $r)
    {
        return ['data' => $r->user('customer')->addresses()->orderByDesc('is_default')->orderByDesc('id')->get()];
    }

    public function storeAddress(Request $r)
    {
        $u = $r->user('customer');
        $v = $r->validate($this->rules());
        $v['phone'] = Notifier::normalizePhone($v['phone']);

        return response()->json(['data' => DB::transaction(function () use ($u, $v) {
            $first = ! $u->addresses()->exists();
            $default = $first || ! empty($v['is_default']);
            if ($default) {
                $u->addresses()->update(['is_default' => false]);
            }

            return $u->addresses()->create($v + ['is_default' => $default]);
        })], 201);
    }

    private function own(Request $r, int $id): Address
    {
        return $r->user('customer')->addresses()->findOrFail($id);
    }

    public function updateAddress(Request $r, int $id)
    {
        $a = $this->own($r, $id);
        $v = $r->validate($this->rules());
        $v['phone'] = Notifier::normalizePhone($v['phone']);
        DB::transaction(function () use ($r, $a, $v) {
            if (! empty($v['is_default'])) {
                $r->user('customer')->addresses()->update(['is_default' => false]);
            }
            $a->update($v);
        });

        return ['data' => $a->refresh()];
    }

    public function deleteAddress(Request $r, int $id)
    {
        $a = $this->own($r, $id);
        $wasDefault = $a->is_default;
        $a->delete();
        if ($wasDefault) {
            $r->user('customer')->addresses()->orderByDesc('id')->first()?->update(['is_default' => true]);
        }

        return response()->noContent();
    }

    public function wishlist(Request $r, ProductPresenter $presenter)
    {
        $ids = DB::table('wishlists')->where('user_id', $r->user('customer')->id)->orderByDesc('created_at')->pluck('product_id');
        $products = Product::where('status', 'active')->with($presenter->relations())->whereIn('id', $ids)->get()->sortBy(fn ($p) => $ids->search($p->id));

        return ['data' => $products->map(fn ($p) => $presenter->card($p))->values(), 'ids' => $ids];
    }

    public function addWishlist(Request $r, int $productId)
    {
        Product::where('status', 'active')->findOrFail($productId);
        DB::table('wishlists')->insertOrIgnore(['user_id' => $r->user('customer')->id, 'product_id' => $productId, 'created_at' => now(), 'updated_at' => now()]);

        return response()->noContent();
    }

    public function removeWishlist(Request $r, int $productId)
    {
        DB::table('wishlists')->where('user_id', $r->user('customer')->id)->where('product_id', $productId)->delete();

        return response()->noContent();
    }
}

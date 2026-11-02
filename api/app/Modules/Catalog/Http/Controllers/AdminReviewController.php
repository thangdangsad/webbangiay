<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Order\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminReviewController
{
    public function index(Request $r)
    {
        $q = Review::with(['user:id,full_name,email'])->orderByRaw("status = 'pending' desc")->orderByDesc('id');
        if ($r->filled('status')) {
            $q->where('status', $r->query('status'));
        }
        $products = \App\Modules\Catalog\Models\Product::withTrashed()->pluck('name', 'id');
        $page = $q->paginate(20);

        return ['data' => $page->getCollection()->map(fn ($x) => [
            'id' => $x->id, 'product' => $products[$x->product_id] ?? '#'.$x->product_id, 'author' => $x->user->full_name, 'email' => $x->user->email,
            'rating' => $x->rating, 'content' => $x->content, 'status' => $x->status, 'created_at' => $x->created_at,
        ])->values(), 'meta' => ['page' => $page->currentPage(), 'limit' => 20, 'total' => $page->total()]];
    }

    public function update(Request $r, int $id)
    {
        $v = $r->validate(['status' => 'required|in:approved,pending']);
        Review::findOrFail($id)->update($v);
        Cache::tags(['catalog'])->flush();

        return ['data' => ['id' => $id, 'status' => $v['status']]];
    }

    public function destroy(int $id)
    {
        Review::findOrFail($id)->delete();
        Cache::tags(['catalog'])->flush();

        return response()->noContent();
    }
}

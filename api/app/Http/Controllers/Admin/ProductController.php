<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Admin-only product CRUD. Equivalent of admin/themsanpham.php +
 * admin/xulysanpham.php, with two legacy bugs fixed:
 *
 * 1. Raw-interpolated INSERT/UPDATE SQL built directly from $_POST (a real
 *    SQL injection vector) — replaced with Eloquent + validated input.
 * 2. The uploaded file's own (client-controlled) filename was trusted and
 *    used as the stored filename — replaced with a generated UUID
 *    filename, stored on the "public" disk via Storage.
 */
class ProductController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $validated['product_image'] = $this->storeImage($request) ?? null;

        $product = Product::create($validated);

        return response()->json([
            'data' => $product,
        ], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $image = $this->storeImage($request);
        if ($image !== null) {
            $validated['product_image'] = $image;
        }

        $product->update($validated);

        return response()->json([
            'data' => $product,
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:50'],
            'product_quantity' => ['required', 'integer', 'min:0'],
            'product_price' => ['required', 'numeric', 'min:0'],
            'product_discount' => ['nullable', 'numeric', 'min:0'],
            'product_details' => ['nullable', 'string'],
            'product_description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:tbl_category,category_id'],
            // legacy allowed up to 2MB and a wide extension list; keep similar
            // limits but validate real image content, not just the extension.
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ];
    }

    /**
     * Store the uploaded "image" file (if present) under a generated UUID
     * filename on the "public" disk, never trusting the client-supplied
     * filename. Returns the stored filename, or null if no file was given.
     */
    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        /** @var UploadedFile $file */
        $file = $request->file('image');
        // extension() guesses from the file's actual MIME type, never the
        // client-supplied filename/extension.
        $filename = Str::uuid()->toString().'.'.$file->extension();

        Storage::disk('public')->putFileAs('products', $file, $filename);

        return $filename;
    }
}

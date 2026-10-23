<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
 *
 * Product (shoe-level) and ProductVariant (one row per size) are managed
 * together here: a request posts product-level fields plus a nested
 * `variants` array.
 */
class ProductController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $validated['product_image'] = $this->storeImage($request) ?? null;

        $product = DB::transaction(function () use ($validated) {
            $product = Product::create($this->productAttributes($validated));

            foreach ($validated['variants'] ?? [] as $variant) {
                ProductVariant::create([
                    'product_id' => $product->getKey(),
                    'size' => $variant['size'],
                    'product_quantity' => $variant['product_quantity'],
                    'is_active' => true,
                ]);
            }

            return $product;
        });

        return response()->json([
            'data' => $product->load('variants'),
        ], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $image = $this->storeImage($request);
        if ($image !== null) {
            $validated['product_image'] = $image;
        }

        DB::transaction(function () use ($product, $validated) {
            $product->update($this->productAttributes($validated));

            $postedVariants = $validated['variants'] ?? [];

            $existingVariants = ProductVariant::query()
                ->where('product_id', $product->getKey())
                ->get()
                ->keyBy('variant_id');

            $seenVariantIds = [];

            foreach ($postedVariants as $variant) {
                $variantId = $variant['variant_id'] ?? null;
                $existing = $variantId !== null ? $existingVariants->get($variantId) : null;

                if ($existing) {
                    $existing->update([
                        'size' => $variant['size'],
                        'product_quantity' => $variant['product_quantity'],
                    ]);
                    $seenVariantIds[] = $existing->getKey();

                    continue;
                }

                $created = ProductVariant::create([
                    'product_id' => $product->getKey(),
                    'size' => $variant['size'],
                    'product_quantity' => $variant['product_quantity'],
                    'is_active' => true,
                ]);
                $seenVariantIds[] = $created->getKey();
            }

            // Never hard-delete an existing variant row here: a variant can
            // carry order history (via donhang/giaodich pointing at its
            // variant_id), so one omitted from the posted form is instead
            // soft-deactivated.
            $existingVariants
                ->except($seenVariantIds)
                ->each(fn (ProductVariant $variant) => $variant->update(['is_active' => false]));
        });

        return response()->json([
            'data' => $product->refresh()->load('variants'),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function productAttributes(array $validated): array
    {
        return array_diff_key($validated, array_flip(['variants']));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'product_price' => ['required', 'numeric', 'min:0'],
            'product_discount' => ['nullable', 'numeric', 'min:0'],
            'product_details' => ['nullable', 'string'],
            'product_description' => ['nullable', 'string'],
            'product_acitve' => ['nullable', 'boolean'],
            'product_hot' => ['nullable', 'boolean'],
            'category_id' => ['nullable', 'integer', 'exists:tbl_category,category_id'],
            // legacy allowed up to 2MB and a wide extension list; keep similar
            // limits but validate real image content, not just the extension.
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],

            'variants' => ['required', 'array', 'min:1'],
            'variants.*.variant_id' => ['nullable', 'integer', 'exists:product_variant,variant_id'],
            'variants.*.size' => ['required', 'string', 'max:50'],
            'variants.*.product_quantity' => ['required', 'integer', 'min:0'],
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

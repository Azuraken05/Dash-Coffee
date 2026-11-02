<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Added Storage facade for cloud uploads

class MenuController extends Controller
{
    public function index()
    {
        return view('staff.menu', [
            'products' => Product::with('ingredients')->orderBy('name')->get(),
            'ingredients' => Ingredient::orderBy('name')->get(),
            'categories' => collect(['Foods', 'Beverages', 'Others'])
                ->merge(Product::distinct()->pluck('category'))->unique()->values(),
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'required|string|max:30',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:available,out_of_stock,hidden',
            'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'recipe.*.ingredient_id' => 'nullable|exists:ingredients,id',
            'recipe.*.quantity' => 'nullable|numeric|gt:0',
        ], [
            'image.required' => 'Please upload a product picture.',
            'image.mimes' => 'The picture must be a JPG or PNG.',
            'image.max' => 'The picture must be 5 MB or smaller.',
        ]);

        // Typed category: reuse an existing one when only the capital letters differ
        $category = trim($d['category']);
        $existing = Product::whereRaw('LOWER(category) = ?', [mb_strtolower($category)])->value('category');

        $path = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $name = uniqid('p_') . '.' . $file->getClientOriginalExtension();
            
            // Uploads to Cloud Storage (S3/Supabase) instead of the local Vercel folder
            $path = $file->storeAs('uploads/products', $name, 's3');
        }

        $product = Product::create([
            'name' => $d['name'],
            'category' => $existing ?: $category,
            'price' => $d['price'],
            'status' => $d['status'],
            'image' => $path,
        ]);

        $sync = [];
        foreach ($request->input('recipe', []) as $row) {
            if (!empty($row['ingredient_id']) && !empty($row['quantity'])) {
                $sync[$row['ingredient_id']] = ['quantity' => $row['quantity']];
            }
        }
        $product->ingredients()->sync($sync);

        return back()->with('ok', $product->name . ' added.');
    }

    public function status(Request $request, Product $product)
    {
        $product->update($request->validate(['status' => 'required|in:available,out_of_stock,hidden']));

        return back()->with('ok', 'Status updated.');
    }

    public function destroy(Product $product)
    {
        // Deletes the image from Cloud Storage before deleting the database record
        if ($product->image && Storage::disk('s3')->exists($product->image)) {
            Storage::disk('s3')->delete($product->image);
        }
        
        $product->delete();

        return back()->with('ok', 'Product deleted.');
    }
}
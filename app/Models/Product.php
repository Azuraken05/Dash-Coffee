<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $guarded = [];

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class)->withPivot('quantity');
    }

    public function canMake(int $qty = 1): bool
    {
        foreach ($this->ingredients as $i) {
            if ((float) $i->stock < (float) $i->pivot->quantity * $qty) {
                return false;
            }
        }
        return true;
    }

    // hidden | out_of_stock | available (goes out of stock automatically when an ingredient runs out)
    public function displayStatus(): string
    {
        if ($this->status === 'hidden') return 'hidden';
        if ($this->status === 'out_of_stock' || ! $this->canMake()) return 'out_of_stock';
        return 'available';
    }

    // Full web address of the product picture (null if none)
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) return null;

        // Already a full link — use as is
        if (preg_match('#^https?://#i', $this->image)) return $this->image;

        $path = ltrim($this->image, '/');

        // Uploaded pictures live in the Supabase bucket
        return Storage::disk('s3')->url($path);
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('previous_stock', 10, 2)->nullable()->after('quantity');
            $table->decimal('resulting_stock', 10, 2)->nullable()->after('previous_stock');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['previous_stock', 'resulting_stock']);
        });
    }
};

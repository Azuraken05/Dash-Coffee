<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Edit audit: kailan, sino, at bakit na-edit ang order (lalabas bilang "Edited").
            $table->timestamp('edited_at')->nullable();
            $table->unsignedBigInteger('edited_by')->nullable();
            $table->string('edited_by_name')->nullable();   // snapshot ng pangalan, hindi mawawala kahit ma-delete ang account
            $table->string('edit_reason')->nullable();

            // Delete audit: soft delete, kaya nandoon pa rin ang row at makikita ng owner sa sales report.
            $table->softDeletes();                           // deleted_at
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->string('deleted_by_name')->nullable();  // snapshot ng pangalan, hindi mawawala kahit ma-delete ang account
            $table->string('delete_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'edited_at',
                'edited_by',
                'edited_by_name',
                'edit_reason',
                'deleted_by',
                'deleted_by_name',
                'delete_reason',
            ]);
        });
    }
};

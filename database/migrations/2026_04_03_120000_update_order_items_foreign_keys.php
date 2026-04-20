<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prevent order history loss when products/vendors are removed.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['vendeur_id']);

            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign('vendeur_id')->references('id')->on('vendeurs')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['vendeur_id']);

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('vendeur_id')->references('id')->on('vendeurs')->cascadeOnDelete();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('city');
            $table->decimal('order_total', 8, 2);
            $table->string('group_size');
            $table->json('items')->nullable();
            $table->text('lyrics')->nullable();
            $table->string('drive_link')->nullable();
            $table->string('youtube_link')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

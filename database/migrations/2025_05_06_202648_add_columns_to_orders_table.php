<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_total')) {
                $table->decimal('order_total', 10, 2)->nullable(false);
            }
            if (!Schema::hasColumn('orders', 'status')) {
                $table->string('status')->default('pending');
            }
            if (!Schema::hasColumn('orders', 'lyrics')) {
                $table->text('lyrics')->nullable();
            }
            if (!Schema::hasColumn('orders', 'drive_link')) {
                $table->string('drive_link')->nullable();
            }
            if (!Schema::hasColumn('orders', 'group_size')) {
                $table->integer('group_size')->default(1);
            }
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'order_total',
                'status',
                'lyrics',
                'drive_link',
                'group_size'
            ]);
        });
    }
}; 
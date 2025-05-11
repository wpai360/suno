<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('song_requests', function (Blueprint $table) {
            $table->string('youtube_id')->nullable()->after('mp4_path');
        });
    }

    public function down()
    {
        Schema::table('song_requests', function (Blueprint $table) {
            $table->dropColumn('youtube_id');
        });
    }
}; 
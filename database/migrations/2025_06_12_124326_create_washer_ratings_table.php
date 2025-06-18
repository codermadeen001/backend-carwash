<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    Schema::create('washer_ratings', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('washer_id');
    $table->decimal('rating', 3, 1);
    $table->timestamps();

    $table->foreign('user_id')->references('id')->on('app_users')->onDelete('cascade');
    $table->foreign('washer_id')->references('id')->on('app_users')->onDelete('cascade');
    $table->unique(['user_id', 'washer_id']); // Only one rating per user per washer
});

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('washer_ratings');
    }
};

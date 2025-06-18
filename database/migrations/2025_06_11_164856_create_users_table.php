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
        Schema::create('app_users', function (Blueprint $table) {
             $table->id();
             $table->string('email')->unique();
             $table->string('contact')->nullable();
             $table->string('img_url')->nullable();
             $table->enum('role', ['admin', 'car detailer', 'client'])->default('client');
             $table->string('name')->nullable();
             $table->string('password');
             $table->boolean('status')->default(false);
             $table->decimal('wallet', 10, 2)->default(0);
             $table->boolean('availability')->default(true);
             $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};

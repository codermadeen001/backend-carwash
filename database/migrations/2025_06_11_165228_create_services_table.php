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
       Schema::create('services', function (Blueprint $table) {
         $table->id();
         $table->string('package_type');
         $table->string('img_url')->nullable();
         $table->decimal('price', 10, 2);
         $table->text('description')->nullable();
         $table->integer('duration')->default(30); // in minutes
         $table->text('list')->nullable();
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
        Schema::dropIfExists('services');
    }
};

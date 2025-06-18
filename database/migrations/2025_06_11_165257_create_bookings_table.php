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
    Schema::create('bookings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
    $table->foreignId('washing_point_id')->constrained('washing_points')->onDelete('cascade');
    $table->foreignId('client_id')->constrained('app_users')->onDelete('cascade');
    $table->foreignId('washer_id')->constrained('app_users')->onDelete('cascade');
    $table->dateTime('time');
    $table->string('receipt')->nullable();
    $table->string('plate_number');
    $table->enum('status', ['active', 'completed', 'cancelled']);
    $table->decimal('price', 8, 2);
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
        Schema::dropIfExists('bookings');
    }
};

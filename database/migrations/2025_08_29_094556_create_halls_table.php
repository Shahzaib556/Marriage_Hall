<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('halls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id'); // Hall Owner
            $table->string('name');
            $table->string('location');
            $table->integer('capacity');
            $table->decimal('pricing', 10, 2);
            $table->json('facilities')->nullable(); // store as JSON
            $table->json('images')->nullable();     // array of image paths
            $table->enum('status', ['pending', 'approved', 'inactive'])->default('pending');
            $table->timestamps();

            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('halls');
    }
};

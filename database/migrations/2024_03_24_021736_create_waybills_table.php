<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('waybills', function (Blueprint $table) {
            $table->uuid('id')->primary();
                //data yang dimasukkan ke waybill
                    $table->string('waybill');
                    $table->string('courier');
                    $table->string('origin')->nullable();
                    $table->string('destination')->nullable();
                    $table->string('origin_address')->nullable();
                    $table->string('destination_address')->nullable();
                    $table->enum('status', ['on progress', 'delivered', 'returned', 'failed']);
                    $table->uuid('user_id')->nullable();
                    $table->enum('status_loop', ['none', 'three', 'six']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waybills');
    }
};

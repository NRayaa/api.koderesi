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
        Schema::create('manifests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            //data manifest
                $table->string('note')->nullable();
                $table->string('status')->nullable();
                $table->foreignUuid('waybill_id')->constrained('waybills')->cascadeOnDelete();
                $table->dateTime('date_manifest')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manifests');
    }
};

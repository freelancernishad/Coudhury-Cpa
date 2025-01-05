<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicePurchasedFilesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_purchased_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_purchased_id'); // Foreign key to ServicePurchased
            $table->string('file_name'); // Original file name
            $table->string('file_path'); // Path to the stored file
            $table->string('file_extension'); // File extension (e.g., pdf, jpg)
            $table->unsignedBigInteger('file_size'); // File size in bytes
            $table->string('mime_type')->nullable(); // MIME type (e.g., application/pdf)
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('service_purchased_id')
                  ->references('id')
                  ->on('service_purchased')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_purchased_files');
    }
}

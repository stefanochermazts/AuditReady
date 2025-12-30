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
        Schema::create('evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->onDelete('cascade');
            $table->foreignId('uploader_id')->constrained('users')->onDelete('cascade');
            $table->string('filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size'); // File size in bytes
            $table->string('stored_path'); // Path in storage (encrypted file)
            $table->string('checksum', 64); // SHA-256 checksum of plaintext
            $table->unsignedInteger('version')->default(1);
            $table->text('encrypted_key'); // Encryption key encrypted with app key
            $table->string('iv', 24); // Base64-encoded IV for AES-256-CBC
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index(['audit_id', 'version']);
            $table->index('uploader_id');
            $table->index('checksum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidences');
    }
};

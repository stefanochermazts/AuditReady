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
        Schema::create('control_owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_id')->constrained('controls')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role_name')->nullable(); // es: "CISO", "IT Manager"
            $table->enum('responsibility_level', ['primary', 'secondary', 'consultant'])->default('primary');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('control_id');
            $table->index('user_id');
            $table->unique(['control_id', 'user_id']); // Un utente può essere owner di un controllo solo una volta
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('control_owners');
    }
};

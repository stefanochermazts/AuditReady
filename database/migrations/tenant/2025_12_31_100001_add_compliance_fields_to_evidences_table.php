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
        Schema::table('evidences', function (Blueprint $table) {
            // Category and Document Info
            $table->string('category')->nullable()->after('filename'); // policy, procedure, incident_report, continuity_plan, vendor_document, training_record, etc.
            $table->date('document_date')->nullable()->after('category'); // Original document date (not upload date)
            $table->string('document_type')->nullable()->after('document_date'); // policy, procedure, report, certificate, etc.
            $table->string('supplier')->nullable()->after('document_type'); // Supplier/origin of document
            
            // Regulatory References
            $table->text('regulatory_reference')->nullable()->after('supplier'); // e.g., "ISO 27001:2022 A.5.1.1", "GDPR Art. 32"
            $table->text('control_reference')->nullable()->after('regulatory_reference'); // Internal control reference, e.g., "CTRL-001"
            
            // Validation
            $table->enum('validation_status', ['pending', 'approved', 'rejected', 'needs_revision'])->default('pending')->after('control_reference');
            $table->foreignId('validated_by')->nullable()->after('validation_status')->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            $table->text('validation_notes')->nullable()->after('validated_at');
            $table->date('expiry_date')->nullable()->after('validation_notes');
            
            // Organization and Metadata
            $table->json('tags')->nullable()->after('expiry_date'); // Array of tags for categorization
            $table->text('notes')->nullable()->after('tags'); // Additional notes/metadata
            $table->enum('confidentiality_level', ['public', 'internal', 'confidential', 'restricted'])->default('internal')->after('notes');
            $table->integer('retention_period_years')->nullable()->default(7)->after('confidentiality_level'); // Retention period in years
            
            // Indexes
            $table->index('category');
            $table->index('validation_status');
            $table->index('validated_by');
            $table->index('expiry_date');
            $table->index('confidentiality_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evidences', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropIndex(['category']);
            $table->dropIndex(['validation_status']);
            $table->dropIndex(['validated_by']);
            $table->dropIndex(['expiry_date']);
            $table->dropIndex(['confidentiality_level']);
            
            $table->dropColumn([
                'category',
                'document_date',
                'document_type',
                'supplier',
                'regulatory_reference',
                'control_reference',
                'validation_status',
                'validated_by',
                'validated_at',
                'validation_notes',
                'expiry_date',
                'tags',
                'notes',
                'confidentiality_level',
                'retention_period_years',
            ]);
        });
    }
};

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
        Schema::table('audits', function (Blueprint $table) {
            // Audit Type and Compliance
            $table->enum('audit_type', ['internal', 'external', 'certification', 'compliance'])->default('internal')->after('status');
            $table->json('compliance_standards')->nullable()->after('audit_type'); // Array: ['ISO 27001', 'GDPR', 'DORA', 'NIS2', 'SOC 2', 'PCI-DSS']
            
            // Scope and Objectives
            $table->text('scope')->nullable()->after('description');
            $table->text('objectives')->nullable()->after('scope');
            
            // Auditor
            $table->foreignId('auditor_id')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            
            // Reference Period
            $table->date('reference_period_start')->nullable()->after('end_date');
            $table->date('reference_period_end')->nullable()->after('reference_period_start');
            
            // Findings and Corrective Actions
            $table->json('findings')->nullable()->after('closed_at'); // Array of findings
            $table->json('corrective_actions')->nullable()->after('findings'); // Array of corrective actions
            $table->json('risk_assessment')->nullable()->after('corrective_actions'); // Risk assessment data
            
            // Compliance References
            $table->text('gdpr_article_reference')->nullable()->after('risk_assessment');
            $table->text('dora_requirement_reference')->nullable()->after('gdpr_article_reference');
            $table->text('nis2_requirement_reference')->nullable()->after('dora_requirement_reference');
            
            // Certification (for external audits)
            $table->string('certification_body')->nullable()->after('nis2_requirement_reference');
            $table->string('certification_number')->nullable()->after('certification_body');
            $table->date('next_audit_date')->nullable()->after('certification_number');
            
            // Indexes
            $table->index('audit_type');
            $table->index('auditor_id');
            $table->index('next_audit_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropForeign(['auditor_id']);
            $table->dropIndex(['audit_type']);
            $table->dropIndex(['auditor_id']);
            $table->dropIndex(['next_audit_date']);
            
            $table->dropColumn([
                'audit_type',
                'compliance_standards',
                'scope',
                'objectives',
                'auditor_id',
                'reference_period_start',
                'reference_period_end',
                'findings',
                'corrective_actions',
                'risk_assessment',
                'gdpr_article_reference',
                'dora_requirement_reference',
                'nis2_requirement_reference',
                'certification_body',
                'certification_number',
                'next_audit_date',
            ]);
        });
    }
};

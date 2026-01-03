<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create tenant-specific tables for tests
        $this->createTenantTables();
    }

    protected function createTenantTables(): void
    {
        // Create controls table
        if (!Schema::hasTable('controls')) {
            Schema::create('controls', function ($table) {
                $table->id();
                $table->enum('standard', ['DORA', 'NIS2', 'ISO27001', 'custom'])->default('custom');
                $table->string('article_reference')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('category')->nullable();
                $table->string('tenant_id')->nullable();
                $table->timestamps();
                $table->index('standard');
                $table->index('category');
            });
        }
        
        // Create control_owners table
        if (!Schema::hasTable('control_owners')) {
            Schema::create('control_owners', function ($table) {
                $table->id();
                $table->foreignId('control_id')->constrained('controls')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('role_name')->nullable();
                $table->enum('responsibility_level', ['primary', 'secondary', 'consultant'])->default('primary');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['control_id', 'user_id']);
            });
        }
        
        // Create audit_logs table (needed by observers)
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function ($table) {
                $table->id();
                $table->string('tenant_id')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('action');
                $table->string('model_type')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->json('payload')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('signature', 64);
                $table->timestamp('created_at');
            });
        }
        
        // Create audits table (needed for export tests)
        if (!Schema::hasTable('audits')) {
            Schema::create('audits', function ($table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('status', ['draft', 'in_progress', 'closed', 'archived'])->default('draft');
                $table->string('audit_type')->nullable();
                $table->json('compliance_standards')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamp('control_ownership_snapshot_at')->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->foreignId('auditor_id')->nullable()->constrained('users')->onDelete('set null');
                $table->date('reference_period_start')->nullable();
                $table->date('reference_period_end')->nullable();
                $table->json('findings')->nullable();
                $table->json('corrective_actions')->nullable();
                $table->json('risk_assessment')->nullable();
                $table->text('gdpr_article_reference')->nullable();
                $table->text('dora_requirement_reference')->nullable();
                $table->text('nis2_requirement_reference')->nullable();
                $table->string('certification_body')->nullable();
                $table->string('certification_number')->nullable();
                $table->date('next_audit_date')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
        
        // Create roles and permissions tables (needed for RBAC tests)
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }
        
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }
        
        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function ($table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                $table->primary(['role_id', 'model_id', 'model_type']);
            });
        }
    }
}

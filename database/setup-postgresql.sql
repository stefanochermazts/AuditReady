-- ============================================
-- PostgreSQL Setup Script for AuditReady
-- ============================================
-- Questo script crea il database centrale e l'utente per AuditReady
-- Eseguire come utente postgres: sudo -u postgres psql -f database/setup-postgresql.sql
-- ============================================

-- Database Centrale (Tenants Metadata)
CREATE DATABASE auditready_tenants;

-- Utente per Database Centrale
CREATE USER auditready_tenants_user WITH PASSWORD 'change_this_password_in_production';

-- Assegna privilegi database centrale
GRANT ALL PRIVILEGES ON DATABASE auditready_tenants TO auditready_tenants_user;

-- Connetti al database centrale
\c auditready_tenants

-- Assegna privilegi schema (PostgreSQL 15+)
GRANT ALL ON SCHEMA public TO auditready_tenants_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO auditready_tenants_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO auditready_tenants_user;

-- Abilita estensioni necessarie
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Messaggio di conferma
\echo 'Database centrale auditready_tenants creato con successo!'
\echo 'IMPORTANTE: Cambia la password in produzione!'

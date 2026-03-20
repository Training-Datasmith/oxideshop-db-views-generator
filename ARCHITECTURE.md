# Architecture: oxideshop-db-views-generator

## Purpose

Generates multi-language database views for the OXID eShop. OXID stores language-specific data in separate tables per language (e.g., `oxarticles`, `oxarticles_1`, `oxarticles_2`). This tool creates SQL `VIEW` definitions that `UNION ALL` those tables so queries can access translated content through a single view.

## Directory Structure

```
src/
  Views_Generator.php   — Generates and executes CREATE VIEW statements for all OXID shop tables
```

## Key Design Decisions

- **Single-class design**: All logic lives in `Views_Generator`; it queries the database schema to discover tables and language count, then generates the SQL dynamically
- **UNION ALL views**: Views combine the base table and all `_N` language variant tables; applications query the view and filter by `oxlang` field
- **Schema-aware**: The generator reads `INFORMATION_SCHEMA` to find which tables have language variants rather than using a hardcoded list

## Extension Points

- Inject a custom database adapter by passing it to the constructor to use outside of OXID's DI container

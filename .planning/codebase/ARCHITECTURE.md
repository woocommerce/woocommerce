# Architecture

**Analysis Date:** 2026-02-02

## Pattern Overview

**Overall:** Modular Monorepo with Mixed Modern/Legacy Patterns

**Key Characteristics:**

-   Monorepo structure using pnpm workspaces for multiple packages
-   Mixed architecture: modern PHP (PSR-4, DI) alongside legacy WordPress patterns
-   Clear separation between backend (PHP) and frontend (React/TypeScript)
-   Package-based architecture for reusable components
-   Plugin-centric architecture following WordPress conventions

## Layers

**Core Plugin Layer:**

-   Purpose: Main WooCommerce plugin functionality
-   Location: `plugins/woocommerce/`
-   Contains: Entry point, legacy code, modern code, assets
-   Depends on: WordPress core, external packages
-   Used by: WordPress, extensions, themes

**Modern PHP Layer (src/):**

-   Purpose: New PHP code following PSR-4 and dependency injection
-   Location: `plugins/woocommerce/src/`
-   Contains: Business logic, services, controllers, internal APIs
-   Depends on: Container, WordPress APIs (via proxies)
-   Used by: Legacy code (via container), REST API, admin UI

**Legacy PHP Layer (includes/):**

-   Purpose: Historical WordPress-style code, maintained for compatibility
-   Location: `plugins/woocommerce/includes/`
-   Contains: Classes, functions, hooks following WP conventions
-   Depends on: WordPress core directly
-   Used by: Themes, extensions, modern code (via LegacyProxy)

**Admin Client Layer:**

-   Purpose: React-based admin interface
-   Location: `plugins/woocommerce/client/admin/`
-   Contains: React components, TypeScript code, admin UI
-   Depends on: WooCommerce REST/Store APIs, WordPress packages
-   Used by: WordPress admin interface

**Blocks Layer:**

-   Purpose: Block editor components for store frontend
-   Location: `plugins/woocommerce/src/Blocks/`
-   Contains: Block types, Store API endpoints, frontend logic
-   Depends on: WordPress blocks, Store API
-   Used by: Block themes, frontend store

**Shared Packages Layer:**

-   Purpose: Reusable components across projects
-   Location: `packages/js/*`, `packages/php/*`
-   Contains: Libraries, utilities, shared components
-   Depends on: Minimal external dependencies
-   Used by: All WooCommerce projects in monorepo

## Data Flow

**Admin UI Request Flow:**

1. User interacts with React component in `client/admin/`
2. Component dispatches action to data store
3. Store makes REST API call to `/wp-json/wc/v3/*`
4. API controller in `src/Admin/API/` handles request
5. Controller uses services from `src/Internal/` via DI container
6. Service interacts with database via data stores
7. Response flows back through API to React component

**Frontend Store Flow:**

1. Customer visits store page
2. Block renders using components from `src/Blocks/`
3. Block fetches data via Store API `/wp-json/wc/store/*`
4. Store API controller processes request
5. Data formatted and returned to block
6. Block updates UI with received data

**State Management:**

-   Admin: Redux-style data stores with wp.data
-   Frontend: WordPress Interactivity API for blocks
-   Server: Stateless request handling with database persistence

## Key Abstractions

**Container (DI):**

-   Purpose: Dependency injection and class resolution
-   Examples: `src/Container.php`, `$GLOBALS['wc_container']`
-   Pattern: PSR-11 compatible container with automatic resolution

**Service Classes:**

-   Purpose: Business logic encapsulation
-   Examples: `src/Internal/*/Service.php` files
-   Pattern: Single responsibility, injected dependencies

**REST Controllers:**

-   Purpose: API endpoint handling
-   Examples: `src/Admin/API/*.php`, `src/StoreApi/Routes/*.php`
-   Pattern: WordPress REST API extension

**Data Stores:**

-   Purpose: Database abstraction
-   Examples: `includes/data-stores/`, Custom Tables
-   Pattern: Repository pattern with WordPress integration

## Entry Points

**Main Plugin:**

-   Location: `woocommerce.php`
-   Triggers: WordPress plugin system
-   Responsibilities: Initialize autoloader, container, main WooCommerce class

**Admin Interface:**

-   Location: `client/admin/index.tsx`
-   Triggers: WordPress admin page load
-   Responsibilities: Mount React app, initialize data stores

**REST APIs:**

-   Location: `src/Admin/API/`, `src/StoreApi/`
-   Triggers: HTTP requests to API endpoints
-   Responsibilities: Handle API requests, return JSON responses

**CLI Commands:**

-   Location: `includes/cli/`
-   Triggers: WP-CLI commands
-   Responsibilities: Batch operations, maintenance tasks

## Error Handling

**Strategy:** Mixed approach with gradual modernization

**Patterns:**

-   Modern code: Exceptions with try-catch blocks
-   Legacy code: WP_Error objects and error codes
-   API responses: Standardized error format with status codes
-   Frontend: Error boundaries and fallback UI

## Cross-Cutting Concerns

**Logging:** `LoggingUtil` class with remote logging capability
**Validation:** Input sanitization at API boundaries, model validation
**Authentication:** WordPress user system, nonces for CSRF, JWT for some APIs

---

_Architecture analysis: 2026-02-02_

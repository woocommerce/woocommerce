# WooCommerce REST API Documentation Generator - Implementation Plan

## Overview

A PHP 8.5 CLI tool that generates static documentation for the WooCommerce REST API from WordPress schema data and custom endpoint descriptors.

---

## 1. Directory Structure

```
plugins/woocommerce/
└── docs/
    └── rest-api/
        ├── bin/
        │   ├── generate-docs.php          # Main CLI entry point
        │   └── src/
        │       ├── Command/
        │       │   ├── CommandInterface.php
        │       │   ├── CompareCommand.php
        │       │   ├── GenerateDescriptorsCommand.php
        │       │   ├── GenerateSiteCommand.php
        │       │   ├── GenerateOpenApiCommand.php
        │       │   └── ValidateCommand.php
        │       ├── Parser/
        │       │   ├── SchemaParser.php
        │       │   ├── DescriptorParser.php
        │       │   └── DefaultCategoriesParser.php
        │       ├── Generator/
        │       │   ├── SiteGenerator.php
        │       │   ├── OpenApiGenerator.php
        │       │   └── DescriptorGenerator.php
        │       ├── Model/
        │       │   ├── Endpoint.php
        │       │   ├── EndpointDescriptor.php
        │       │   ├── Category.php
        │       │   └── Schema.php
        │       ├── Validator/
        │       │   └── DescriptorValidator.php
        │       ├── Util/
        │       │   ├── RouteFormatter.php
        │       │   └── HttpClient.php
        │       └── Template/
        │           ├── TemplateEngine.php
        │           └── templates/
        │               ├── layout.php
        │               ├── sidebar.php
        │               ├── endpoint.php
        │               └── category.php
        ├── assets/
        │   ├── css/
        │   │   └── style.css
        │   ├── js/
        │   │   └── main.js
        │   └── images/
        │       └── woo_logo.png
        ├── endpoint-descriptors/
        │   └── .gitkeep
        ├── default-categories.md
        ├── temp/                           # .gitignore'd
        │   └── rest-api-schema.json
        └── html/                           # .gitignore'd - generated site output
```

---

## 2. CLI Interface

### Entry Point: `generate-docs.php`

```bash
# Fetch schema and compare with existing descriptors
php generate-docs.php compare --url=http://localhost:8888

# Compare using cached schema
php generate-docs.php compare

# Generate missing endpoint descriptors
php generate-docs.php generate-descriptors --url=http://localhost:8888

# Validate all descriptor files
php generate-docs.php validate

# Generate static documentation site
php generate-docs.php generate-site [--exclude-incomplete]

# Generate OpenAPI schema
php generate-docs.php generate-openapi

# Show help
php generate-docs.php help
```

### Common Options

- `--url=<URL>` - WordPress site URL to fetch schema from
- `--auth=<TOKEN>` - Optional authentication token (Bearer)
- `--verbose` - Show detailed output
- `--quiet` - Suppress non-error output

---

## 3. Endpoint Descriptor Format

### File Naming Convention

```
endpoint-descriptors/
└── v3/
    └── Products/
        ├── get__wc_v3_products.md
        ├── post__wc_v3_products.md
        ├── get__wc_v3_products_id.md
        └── post_put_patch__wc_v3_products_id.md
```

Pattern: `{VERB(S)}__{ROUTE_NORMALIZED}.md`
- Verbs: lowercase, underscore-separated if multiple
- Route: slashes become underscores, remove regex patterns, use param names

### Descriptor File Structure

```markdown
|          |                                                              |
|----------|--------------------------------------------------------------|
| category | v3/Products                                                  |
| route    | /wc/v3/products/(?P<id>[\d]+)                                |
| name     | Get a product                                                |
| verb     | GET                                                          |
| auth     | required                                                     |
| ignore   | false                                                        |

Retrieves a single product by its ID.

## Notes

This endpoint returns the full product object including all variations data.
```

### Header Fields

| Field | Required | Description |
|-------|----------|-------------|
| `category` | Yes | Hierarchical category path (up to 4 levels), e.g., `v3/Products/Variations` |
| `route` | Yes | Exact route pattern from WordPress schema |
| `name` | Yes | Human-readable short name |
| `verb` | Yes | HTTP verb(s), comma-separated if multiple identical endpoints |
| `auth` | No | `required`, `optional`, or omit to infer from schema |
| `ignore` | No | `true` to exclude from generation |

---

## 4. Default Categories Configuration

### File: `default-categories.md`

```markdown
# Default Categories

Routes are matched top-to-bottom. First match wins.

|                                    |                        |
|------------------------------------|------------------------|
| /wc/v1/products(/.*)?              | v1/Products            |
| /wc/v1/products/.*/variations(/.*)? | v1/Products/Variations |
| /wc/v1/orders(/.*)?                | v1/Orders              |
| /wc/v2/products(/.*)?              | v2/Products            |
| /wc/v2/orders(/.*)?                | v2/Orders              |
| /wc/v3/products(/.*)?              | v3/Products            |
| /wc/v3/products/.*/variations(/.*)? | v3/Products/Variations |
| /wc/v3/orders(/.*)?                | v3/Orders              |
| /wc/v3/customers(/.*)?             | v3/Customers           |
| /wc/v3/reports(/.*)?               | v3/Reports             |
| /wc/v3/settings(/.*)?              | v3/Settings            |
| /wc/v3/webhooks(/.*)?              | v3/Webhooks            |
| /wc/v3/shipping(/.*)?              | v3/Shipping            |
| /wc/v3/taxes(/.*)?                 | v3/Taxes               |
| /wc/v3/coupons(/.*)?               | v3/Coupons             |
| /wc/v3/payment-gateways(/.*)?      | v3/Payment Gateways    |
| /wc/v3/data(/.*)?                  | v3/Data                |
| /wc/v3/system-status(/.*)?         | v3/System Status       |
```

---

## 5. Commands Implementation

### 5.1 Compare Command

**Purpose:** List endpoints in schema that don't have descriptors, and descriptors marked as ignored.

**Algorithm:**
1. Load schema from cache or fetch from URL
2. Filter to `/wc/*` routes only
3. Load all descriptor files
4. For each schema endpoint (route + verb combination):
   - Check if matching descriptor exists
   - Check if descriptor has `ignore: true`
5. Output:
   - Missing descriptors (with route and verb)
   - Ignored descriptors
   - Summary counts

**Output Example:**
```
Missing endpoint descriptors:
  - GET /wc/v3/products/categories
  - POST /wc/v3/products/categories
  - DELETE /wc/v3/products/categories/(?P<id>[\d]+)

Ignored endpoints:
  - GET /wc/v1/legacy-endpoint (marked ignore in descriptor)

Summary:
  Total endpoints in schema: 142
  With descriptors: 135
  Missing descriptors: 5
  Ignored: 2
```

### 5.2 Generate Descriptors Command

**Purpose:** Create template descriptor files for endpoints without descriptors.

**Algorithm:**
1. Run compare logic to find missing endpoints
2. For each missing endpoint:
   - Determine category from `default-categories.md` (or `UNCATEGORIZED`)
   - Generate filename from verb + route
   - Create descriptor with:
     - Inferred category
     - Route (exact from schema)
     - Placeholder name: `TODO: Add name`
     - Verb(s)
     - `ignore: true` (template)
   - Write to appropriate directory (create if needed)
3. Report created files

### 5.3 Validate Command

**Purpose:** Check all descriptor files for correctness.

**Validations:**
- File parses correctly (valid markdown table format)
- Required fields present: `category`, `route`, `name`, `verb`
- Category depth ≤ 4 levels
- Verb is valid HTTP method (GET, POST, PUT, PATCH, DELETE)
- Route matches an endpoint in the schema
- No duplicate descriptors (same route + verb)
- `ignore` and `auth` values are valid if present

**Output:**
```
Validating 142 descriptor files...

Errors:
  endpoint-descriptors/v3/Products/get__products.md:
    - Missing required field: name
    - Route not found in schema: /wc/v3/prodcts

Warnings:
  endpoint-descriptors/v3/Orders/get__orders_id.md:
    - Category depth exceeds 4 levels

Summary: 2 errors, 1 warning in 142 files
```

### 5.4 Generate Site Command

**Purpose:** Generate the static HTML documentation site.

**Options:**
- `--exclude-incomplete` - Skip endpoints with missing response schemas

**Algorithm:**
1. Load and validate all descriptors (fail if validation errors)
2. Load schema
3. Build category tree from descriptors
4. For each endpoint:
   - Merge descriptor data with schema data
   - Format route for display (`{id}` style)
   - Generate curl example
   - Render endpoint HTML
5. Generate sidebar navigation HTML
6. Generate index page with category listing
7. Copy assets (CSS, JS, images)
8. Write all files to `html/` directory

**Generated Structure:**
```
html/
├── index.html
├── css/
│   └── style.css
├── js/
│   └── main.js
├── images/
│   └── woo_logo.png
└── endpoints/
    ├── v3/
    │   ├── products/
    │   │   ├── index.html        # Category page
    │   │   ├── get-products.html
    │   │   ├── post-products.html
    │   │   └── get-products-id.html
    │   └── orders/
    │       └── ...
    └── v2/
        └── ...
```

### 5.5 Generate OpenAPI Command

**Purpose:** Generate OpenAPI 3.0 schema JSON file.

**Algorithm:**
1. Load all non-ignored descriptors
2. Load WordPress schema
3. Build OpenAPI structure:
   - `openapi: "3.0.0"`
   - `info`: title, version, description
   - `servers`: placeholder (configurable)
   - `paths`: from endpoints
   - `components/schemas`: from WordPress schema definitions
4. Write to `plugins/woocommerce/src/Internal/RestApi/openapi.json`
5. Generate MD5 hash, write to `openapi.hash`

**OpenAPI Output Location:**
```
plugins/woocommerce/src/Internal/RestApi/
├── openapi.json
└── openapi.hash
```

---

## 6. Site Design

### Layout

```
┌─────────────────────────────────────────────────────────────────┐
│  [WooCommerce Logo]  REST API Documentation                     │
├───────────────┬─────────────────────────────────────────────────┤
│               │                                                 │
│  ▼ v3         │  GET /wc/v3/products/{id}                       │
│    ▼ Products │  ─────────────────────────────────────────────  │
│      • List   │                                                 │
│      • Get    │  Get a product                                  │
│      • Create │                                                 │
│    ▼ Orders   │  Retrieves a single product by its ID.          │
│      • List   │                                                 │
│      • Get    │  ## Parameters                                  │
│  ▼ v2         │                                                 │
│    ► Products │  | Name | Type   | In   | Required | Desc     | │
│               │  |------|--------|------|----------|----------| │
│               │  | id   | integer| path | Yes      | Product  | │
│               │                                                 │
│               │  ## Response                                    │
│               │                                                 │
│               │  ```json                                        │
│               │  {                                              │
│               │    "id": 123,                                   │
│               │    "name": "Product Name",                      │
│               │    ...                                          │
│               │  }                                              │
│               │  ```                                            │
│               │                                                 │
│               │  ## Example                                     │
│               │                                                 │
│               │  ```bash                                        │
│               │  curl -X GET \                                  │
│               │    https://example.com/wp-json/wc/v3/products/1 │
│               │    -H "Authorization: Bearer <token>"           │
│               │  ```                                            │
│               │                                                 │
└───────────────┴─────────────────────────────────────────────────┘
```

### CSS Approach

- Simple, clean design
- No framework dependencies
- CSS custom properties for easy theming
- Responsive (collapsible sidebar on mobile)
- Syntax highlighting for code blocks (minimal inline CSS or small library)

### JavaScript

Minimal, only for:
- Sidebar category expand/collapse
- Copy-to-clipboard for code blocks
- Mobile menu toggle

---

## 7. OpenAPI Endpoint

### New REST API Endpoint

**File:** `plugins/woocommerce/src/Internal/RestApi/OpenApiController.php`

```php
<?php
namespace Automattic\WooCommerce\Internal\RestApi;

class OpenApiController {
    public function register_routes() {
        register_rest_route('wc', '/openapi', [
            'methods' => 'GET',
            'callback' => [$this, 'get_schema'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function get_schema(\WP_REST_Request $request) {
        $schema_path = __DIR__ . '/openapi.json';
        $hash_path = __DIR__ . '/openapi.hash';

        if (!file_exists($schema_path)) {
            return new \WP_Error('not_found', 'OpenAPI schema not available', ['status' => 404]);
        }

        $etag = file_exists($hash_path) ? trim(file_get_contents($hash_path)) : null;

        // Handle If-None-Match
        $if_none_match = $request->get_header('If-None-Match');
        if ($etag && $if_none_match === $etag) {
            return new \WP_REST_Response(null, 304);
        }

        $schema = json_decode(file_get_contents($schema_path), true);

        $response = new \WP_REST_Response($schema);
        if ($etag) {
            $response->header('ETag', $etag);
        }
        $response->header('Content-Type', 'application/json');

        return $response;
    }
}
```

---

## 8. Schema Analysis

### Flagging Incomplete Endpoints

During site generation or via a dedicated flag, analyze endpoints for:
- Missing response schema
- Missing argument descriptions
- Empty or placeholder descriptions

Output as warnings, optionally exclude from generated site.

---

## 9. Error Handling

### Strategy

| Scenario | Behavior |
|----------|----------|
| Schema URL unreachable | Error message, exit code 1 |
| No cached schema and no URL provided | Error message, exit code 1 |
| Single malformed descriptor | Collect error, continue, report all at end |
| Multiple malformed descriptors | Collect all errors, report at end, exit code 1 |
| Missing required field in descriptor | Validation error |
| Descriptor route not in schema | Validation warning |
| Write permission error | Error message, exit code 1 |

### Exit Codes

- `0` - Success
- `1` - Error (see output for details)
- `2` - Validation errors found

---

## 10. Implementation Phases

### Phase 1: Foundation
1. Set up directory structure
2. Implement CLI entry point with argument parsing
3. Implement schema fetching and caching (`HttpClient`, `SchemaParser`)
4. Implement descriptor parsing (`DescriptorParser`)
5. Implement default categories parsing (`DefaultCategoriesParser`)
6. Create models (`Endpoint`, `EndpointDescriptor`, `Category`)

### Phase 2: Core Commands
7. Implement Compare command
8. Implement Generate Descriptors command
9. Implement Validate command

### Phase 3: Site Generation
10. Implement template engine
11. Create HTML templates
12. Implement site generator
13. Create CSS styles
14. Create JavaScript functionality
15. Implement Generate Site command

### Phase 4: OpenAPI
16. Implement OpenAPI generator
17. Implement OpenAPI REST endpoint
18. Implement Generate OpenAPI command

### Phase 5: Polish
19. Add pnpm script wrapper
20. Add comprehensive error messages
21. Create initial `default-categories.md` with WooCommerce routes
22. Documentation (README for the tool)

---

## 11. pnpm Integration

### Package.json Scripts

```json
{
  "scripts": {
    "docs:rest-api:compare": "php docs/rest-api/bin/generate-docs.php compare",
    "docs:rest-api:generate-descriptors": "php docs/rest-api/bin/generate-docs.php generate-descriptors",
    "docs:rest-api:validate": "php docs/rest-api/bin/generate-docs.php validate",
    "docs:rest-api:generate-site": "php docs/rest-api/bin/generate-docs.php generate-site",
    "docs:rest-api:generate-openapi": "php docs/rest-api/bin/generate-docs.php generate-openapi"
  }
}
```

Usage:
```bash
pnpm docs:rest-api:compare -- --url=http://localhost:8888
pnpm docs:rest-api:generate-site
```

---

## 12. Files to Add to .gitignore

```gitignore
# REST API docs generator
docs/rest-api/temp/
docs/rest-api/html/
```

---

## 13. Testing Strategy

### Manual Testing

1. Run against local WordPress + WooCommerce installation
2. Verify generated site renders correctly
3. Verify OpenAPI schema validates against OpenAPI 3.0 spec
4. Test edge cases (empty schema, malformed descriptors)

### Automated Testing (Future)

- Unit tests for parsers
- Unit tests for generators
- Integration tests for CLI commands

---

## Summary

This implementation plan provides a complete roadmap for building the REST API documentation generator. The phased approach allows for incremental development and testing, with the most critical functionality (compare, generate descriptors) available first.

Key design decisions:
- Pure PHP 8.5 with no external dependencies
- Markdown table format for descriptors (as specified)
- Minimal JavaScript for site interactivity
- Combined OpenAPI schema for all API versions
- Comprehensive validation before generation
- Clear error handling with appropriate exit codes

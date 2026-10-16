# WooCommerce Block Entities

This module contains the entity helpers for WooCommerce. Entities provide a standardized way to interact with WordPress data stores and enable consistent data access patterns across the admin interface.

## Overview

The pure entity helpers in this directory are bundled into their consumers and can be tree-shaken. Registration lives separately in `../entity-registration` and builds as a dedicated script that loads consistently on every admin screen.

For backward compatibility, the registration script continues to expose the runtime helpers on `wc.wcEntities`. That global API is deprecated as of WooCommerce 11.1.0 and emits a warning when a helper is called.

## Available Entities

### Product Entity

The product entity provides access to WooCommerce product data through WordPress's core data store. It includes:

-   **Constants**: Entity name, kind, and configuration
-   **Types**: TypeScript interfaces for product data structures
-   **Guards**: Runtime type checking utilities
-   **Hooks**: React hooks for data fetching and manipulation

## Usage

### Automatic Registration

Entities are automatically registered on every admin page through the `wc-entities` script. The script performs registration as a side effect and temporarily retains the deprecated `wc.wcEntities` global for backward compatibility.

### Deprecated manual registration

The manual registration functions remain exported temporarily for compatibility:

```typescript
import { registerProductEntity } from './entities/register-entities';

registerProductEntity();
```

These functions are deprecated as of WooCommerce 11.1.0 and emit a warning when called. Rely on the automatically loaded `wc-entities` script instead.

### Using Entity Hooks

```typescript
import { useProduct } from './entities/product';

function MyComponent() {
	const { product, isLoading, error } = useProduct( 123 );

	if ( isLoading ) return <div>Loading...</div>;
	if ( error ) return <div>Error: { error.message }</div>;

	return <div>{ product.name }</div>;
}
```

## Benefits

1. **Consistent Availability**: Entities are now available across all admin pages, not just the editor
2. **Better Performance**: Centralized registration reduces duplicate entity definitions
3. **Encapsulation**: Entity helpers remain internal to the bundles that consume them

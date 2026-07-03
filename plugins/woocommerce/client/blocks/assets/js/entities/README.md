# WooCommerce Block Entities

This module contains the entity registration and management system for WooCommerce. Entities provide a standardized way to interact with WordPress data stores and enable consistent data access patterns across the admin interface.

## Overview

Entities have a dedicated module that loads consistently on every admin screen, ensuring they can be accessed and extended throughout the entire admin experience. This makes it possible to use the entities in this folder across all the admin screens.

With this approach, third-party developers can also start using entities outside of the Gutenberg editor whenever needed.

## Source location

This package lives in `plugins/woocommerce/client/blocks/assets/js/entities` because it follows the trunk Blocks source layout while still being emitted as the `wc-entities` script handle and exposed through the `@woocommerce/entities` import name for explicit consumers.

Package-like code that uses public import names can still live in the matching `assets/js` source area and be bundled into `@woocommerce/block-library` when appropriate.

## Available Entities

### Product Entity

The product entity provides access to WooCommerce product data through WordPress's core data store. It includes:

-   **Constants**: Entity name, kind, and configuration
-   **Types**: TypeScript interfaces for product data structures
-   **Guards**: Runtime type checking utilities
-   **Hooks**: React hooks for data fetching and manipulation

## Usage

### Automatic Registration

Entities are automatically registered when the module is loaded. This happens on every admin page through the `wc-entities` script.

### Manual Registration

If you need to register entities manually (e.g., in tests), you can use the registration functions:

```typescript
import { registerProductEntity } from '@woocommerce/entities/register-entities';

// Register the product entity
registerProductEntity();
```

### Using Entity Hooks

```typescript
import { useProduct } from '@woocommerce/entities/product';

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
3. **Developer Experience**: Third-party developers can use entities outside of Gutenberg

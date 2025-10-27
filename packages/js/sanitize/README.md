# @woocommerce/sanitize

WooCommerce HTML sanitization utilities using DOMPurify with trusted types support.

## Features

- **Secure HTML Sanitization**: Uses DOMPurify to sanitize HTML content
- **Trusted Types Support**: Automatically configures trusted types policy to avoid conflicts
- **Configurable**: Supports custom allowed tags and attributes
- **TypeScript Support**: Full TypeScript definitions included

## Installation

This package is part of the WooCommerce monorepo and is automatically available to other packages.

## Usage

### Basic HTML Sanitization

```typescript
import { sanitizeHTML } from '@woocommerce/sanitize';

const cleanHTML = sanitizeHTML('<p>Hello <script>alert("xss")</script> World!</p>');
// Returns: '<p>Hello World!</p>'
```

### React Integration

```javascript
import { sanitizeHTML } from '@woocommerce/sanitize';

function MyComponent( { content } ) {
  const sanitizedContent = {
    __html: sanitizeHTML( content )
  };
  
  return (
    <div dangerouslySetInnerHTML={ sanitizedContent } />
  );
}
```

### Custom Configuration

```javascript
import { sanitizeHTML } from '@woocommerce/sanitize';

const customSanitized = sanitizeHTML(
    html,
    {
        tags: ['p', 'br', 'strong'],
        attr: ['class', 'id']
    }
);
```

## API Reference

### Functions

#### `sanitizeHTML(html: string, config?: SanitizeConfig): string`

Sanitizes HTML content using default allowed tags and attributes.

#### `initializeTrustedTypesPolicy(): void`

Manually initialize the trusted types policy (usually called automatically).

### Constants

#### `DEFAULT_ALLOWED_TAGS`

Default array of allowed HTML tags for basic sanitization.

#### `DEFAULT_ALLOWED_ATTR`

Default array of allowed HTML attributes for basic sanitization.

### Types

#### `SanitizeConfig`

```typescript
interface SanitizeConfig {
  tags?: readonly string[];
  attr?: readonly string[];
}
```

## Trusted Types

This package automatically configures a trusted types policy named `woocommerce-sanitize` to avoid conflicts with DOMPurify's default policy. The policy is initialized when the module is loaded.

## Security

- All HTML content is sanitized using DOMPurify
- XSS attacks are prevented by removing dangerous content
- Trusted types are used when available for additional security
- Configurable allowlists for tags and attributes

## Contributing

This package follows the same contribution guidelines as the main WooCommerce repository. 

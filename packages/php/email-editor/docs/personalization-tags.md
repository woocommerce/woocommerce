# Personalization Tags Documentation

## Table of Contents

-   [What are Personalization Tags?](#what-are-personalization-tags)
-   [Personalization Tags and Bits](#personalization-tags-and-bits)
-   [How They Work](#how-they-work)
-   [Key Components Overview](#key-components-overview)
-   [Editor UI](#editor-ui)
-   [Format](#format)
-   [Context](#context)
-   [Rendering Context and Escaping](#rendering-context-and-escaping)
-   [Core Components](#core-components)
-   [Creating Custom Tags](#creating-custom-tags)
-   [Usage with Renderer](#usage-with-renderer)

## What are Personalization Tags?

Personalization Tags are dynamic placeholders that can be inserted into email content to automatically replace with specific values when emails are sent. They provide a way to create personalized, context-aware email content without manual intervention.

**Key Features:**

-   **Dynamic Content**: Automatically replace tags with relevant data
-   **Context-Aware**: Access to order, customer, store, and site information
-   **Extensible**: Third-party developers can create custom tags

## Personalization Tags and Bits

Personalization Tags share conceptual similarities with WordPress Bits, a proposed system for dynamic tokens in WordPress core. Both systems aim to provide dynamic content replacement capabilities, though they serve different contexts. Personalization Tags are specifically designed for email content personalization within WooCommerce, while Bits are proposed as a general-purpose solution for WordPress content.

Both systems use HTML comment syntax for their tokens. Personalization Tags use the format `<!--[token-name attributes]-->` (e.g., `<!--[my-plugin/formatted-date date="2024-01-15" format="F j, Y"]-->`), which aligns with the "funky comment" approach proposed for Bits. This format provides safe fallback behavior, human-typability, and reliable parsing capabilities. As the WordPress Bits proposal progresses, there may be opportunities to align these systems or migrate Personalization Tags to use the core Bits infrastructure.

For more information about the WordPress Bits proposal, see: [Proposal: Bits as dynamic tokens](https://make.wordpress.org/core/2024/06/08/proposal-bits-as-dynamic-tokens/)

## How They Work

The Personalization Tags system works through a simple architecture:

1. **Registration**: Tags are registered with the `Personalization_Tags_Registry`.
2. **Editor UI**: Registered tags are automatically available in the email editor UI in rich-text toolbar.
3. **Processing**: The `Personalizer` engine processes email content and replaces the tags. The personalizer works on top of `HTML_Tag_Processor`
4. **Context**: The Personalizer accepts context, which is then provided to the Tags.

## Key Components Overview

### 1. Personalization_Tags_Registry

The central registry that manages all personalization tags. It provides:

-   Tag registration and retrieval
-   Duplicate prevention
-   Logging and debugging support

### 2. Personalization_Tag

Individual tag instances that contain:

-   Display name and token
-   Category for organization
-   Callback function for value generation
-   Attributes for configuration

### 3. Personalizer

The main engine that orchestrates the personalization process:

-   Manages context data
-   Coordinates tag processing
-   Handles different content types (body, title, links)

## Editor UI

Registered personalization tags are available for usage in a modal component in the email editor. They are categorized via the category parameter passed during registration.

![Modal listing available personalization tags](https://github.com/user-attachments/assets/1c9761c8-8386-4ac6-9ab4-6386af55a9f2)

When selected from a modal, the tag is placed into a text block displayed in a shortcode-like syntax. It can be edited further by clicking on it.

![Example of an inserted personalization tag inside a paragraph block](https://github.com/user-attachments/assets/7aaf99f0-eb9c-413a-acd9-c5f551bb89d4)

## Format

Personalization Tags use HTML comment syntax with a specific format: `<!--[token-name attributes]-->`. The token name follows a namespaced pattern (e.g., `my-plugin/tag-name`) and can include attributes for configuration. This format ensures safe fallback behavior in browsers and provides reliable parsing capabilities.

**Example usage in a paragraph block:**

```html
<p>
	Hello
	<!--[customer/first-name]-->, your order
	<!--[order/number]-->
	was placed on
	<!--[my-plugin/formatted-date date="order_date" format="F j, Y"]-->
	and is currently
	<!--[order/status]-->. Thank you for your purchase!
</p>
```

This would render as: "Hello John, your order #12345 was placed on January 15, 2024, and is currently Processing. Thank you for your purchase!"

## Context

The personalization context is a simple associative array passed to the Personalizer. It is the integrator's responsibility to build the context and set it to the Personalizer.
The context is then passed to the `Personalization_Tag` callback function and can be used to derive the value.
Note: This is still an early concept, and we may add actions/filters, as well as default context data.

```php
$context = [
    'order'           => $wc_order,         // WooCommerce order object
    'wp_user'         => $user,             // WordPress user object
    'recipient_email' => $email,            // Recipient's email address
    'wc_email'        => $email_object,     // WooCommerce email object
    // ... additional context data
];
```

One key is reserved: `rendering_context` — see [Rendering Context and Escaping](#rendering-context-and-escaping).

## Rendering Context and Escaping

Email content is rendered into different destinations, and the correct value (and its escaping) differs per destination. The caller of `personalize_content()` declares the rendering context of the whole content: `Personalizer::RENDERING_CONTEXT_HTML` (default) for HTML bodies, or `Personalizer::RENDERING_CONTEXT_TEXT` for plain-text content such as the email subject, preheader, or a plain-text body.

The Personalizer passes the context of each replacement to the tag callback under the reserved `rendering_context` key (`Personalizer::RENDERING_CONTEXT_KEY`) of the context array. Any value stored under this key via `set_context()` is overwritten.

| Rendering context | Where the value lands | What the callback must return |
| --- | --- | --- |
| `Personalizer::RENDERING_CONTEXT_HTML` | HTML body content | An HTML fragment. Escape any dynamic data yourself (e.g. `esc_html()`) — unless the tag declares `VALUE_TYPE_TEXT`, in which case return raw text and the Personalizer escapes it (see [Value Type](#value-type)). |
| `Personalizer::RENDERING_CONTEXT_TEXT` | Plain-text output (subject, preheader, plain-text body, `<title>`) | Raw plain text. No HTML markup, no entities, no escaping. |
| `Personalizer::RENDERING_CONTEXT_HREF` | A link URL (`href`) | A raw, unescaped URL — do not pre-escape; URL escaping is applied when the attribute is written. |

If the value is a URL *component* rather than a whole URL (e.g. an email address embedded in a query string), `rawurlencode()` it in the callback — the Personalizer cannot tell the difference.

Inspecting the rendering context is optional — a callback that ignores it simply returns the same value for every destination. A tag that serves different content per destination can switch on the key:

```php
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;

function ( $context ) {
    if ( Personalizer::RENDERING_CONTEXT_TEXT === ( $context[ Personalizer::RENDERING_CONTEXT_KEY ] ?? '' ) ) {
        return 'Save 20% with code SAVE20';
    }
    return '<strong>Save 20%</strong> with code SAVE20';
}
```

### Value Type

Most tags return plain text: names, order numbers, dates. Declare those with `Personalization_Tag::VALUE_TYPE_TEXT`. The callback then always returns the raw, unescaped value, and the Personalizer escapes it to fit the rendering context: `esc_html()` in HTML content, untouched in plain-text output and in link URLs (where `esc_url()` is applied when the attribute is written). Declaring a value type is the expected practice for new tags.

```php
$registry->register(
    new Personalization_Tag(
        'First Name',
        'customer/first-name',
        'Customer',
        function ( $context ) {
            return $context['customer_first_name'] ?? '';
        },
        array(),
        null,
        array(),
        Personalization_Tag::VALUE_TYPE_TEXT
    )
);
```

Note: the `esc_html()` call does not double-encode existing entities, so a raw text value that itself contains an entity-shaped sequence (e.g. a literal `&amp;`) renders as the decoded character (`&`). Always return genuinely raw text.

The default, `Personalization_Tag::VALUE_TYPE_HTML`, inserts the value untouched in every rendering context — including plain-text output; the callback owns escaping and any per-context differences (in the `text` rendering context it must return raw plain text without markup or entities).

## Core Components

### Personalization_Tags_Registry

The central registry for managing personalization tags.

**Key Methods:**

-   `register(Personalization_Tag $tag)`: Register a new tag
-   `unregister(string|Personalization_Tag $token_or_tag)`: Unregister a tag by token or tag instance
-   `get_by_token(string $token)`: Retrieve a tag by its token
-   `get_all()`: Get all registered tags
-   `initialize()`: Initialize the registry and load all providers

**Example Usage:**

Note: You typically won't need to create the registry yourself, but you should register tags via `woocommerce_email_editor_register_personalization_tags` filter which receives the registry via parameter. See [Creating Custom Tags](#creating-custom-tags)

```php
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;

$container = Email_Editor_Container::container();
$registry  = $container->get( Personalization_Tags_Registry::class );
$registry->initialize();


// Register a custom tag
$tag = new Personalization_Tag(
    'Custom Field',
    'my-plugin/custom-field',
    'Customer',
    function( $context ) {
        return $context['custom_value'] ?? '';
    },
    array(),
    null,
    array( 'my-post-type' )
);
$registry->register( $tag );

// Unregister a tag by token string
$registry->unregister( '[my-plugin/custom-field]' );

// Or unregister by passing the tag instance
$registry->unregister( $tag );
```

### Personalization_Tag

The main class representing individual tags.

**Constructor Parameters:**

```php
new Personalization_Tag(
    string $name,                    // Display name in UI
    string $token,                   // Token used in content
    string $category,                // Category for organization
    callable $callback,              // Function to generate value
    array $attributes = [],          // Default attributes
    ?string $value_to_insert = null, // Custom insert value
    array $post_types = [],          // List of supported post types
    string $value_type = Personalization_Tag::VALUE_TYPE_HTML // Type of value the callback returns (VALUE_TYPE_TEXT or VALUE_TYPE_HTML)
);
```

**Key Methods:**

-   `get_name()`: Get the display name
-   `get_token()`: Get the token
-   `get_category()`: Get the category
-   `get_callback()`: Get the callback function
-   `execute_callback($context, $args)`: Execute the callback function
-   `get_attributes()`: Get default attributes
-   `get_value_to_insert()`: Get the value to insert in UI
-   `get_post_types()`: Get the list of post types
-   `get_value_type()`: Get the type of value the callback returns

### Personalizer

Main engine for replacing tags with values in email content.

**Key Methods:**

-   `set_context(array $context)`: Set the personalization context
-   `get_context()`: Get the current context
-   `personalize_content(string $content, string $rendering_context = Personalizer::RENDERING_CONTEXT_HTML)`: Process and personalize content; pass `Personalizer::RENDERING_CONTEXT_TEXT` for plain-text content such as subjects or plain-text bodies

**Example Usage:**

```php
$personalizer = new Personalizer( $registry );
$personalizer->set_context([
    'order' => $order,
    'recipient_email' => 'customer@example.com'
]);

$personalized_content = $personalizer->personalize_content( $email_content );
```

## Creating Custom Tags

### Basic Tag Creation

Create a simple personalization tag:

```php
<?php
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;

add_filter('woocommerce_email_editor_register_personalization_tags', function( $registry ) {
    $registry->register(
        new Personalization_Tag(
            'Customer Age',             // Display name
            'my-plugin/customer-age',   // Token
            'Customer',                 // Category
            function( $context ) {
                if ( isset( $context['order'] ) ) {
                    $customer_id = $context['order']->get_customer_id();
                    if ( $customer_id ) {
                        $birth_date = get_user_meta( $customer_id, 'birth_date', true );
                        if ( $birth_date ) {
                            $age = date_diff( date_create( $birth_date ), date_create( 'today' ) )->y;
                            return (string) $age;
                        }
                    }
                }
                return '';
            }
        )
    );
});
```

### Tag with Parameters

Create a tag that accepts parameters:

```php
$registry->register(
    new Personalization_Tag(
        'Formatted Date',
        'my-plugin/formatted-date',
        'Custom',
        function( $context, $args ) {
            $date   = $args['date'] ?? 'now';
            $format = $args['format'] ?? 'Y-m-d';

            $timestamp = strtotime( $date );
            if ( false === $timestamp ) {
                return 'Invalid date';
            }
            return date( $format, $timestamp );
        },
        [
            'date'   => 'now',
            'format' => 'Y-m-d'
        ]
    )
);
```

**Usage in the email content:**

```html
<p>
	Order placed on:
	<!--[my-plugin/formatted-date date="2024-01-15" format="F j, Y"]-->
</p>
```

## Usage with Renderer

Here's a complete example showing how to render an email using the Renderer and then personalize it with the Personalizer:

```php
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Bootstrap;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;

// Get services from container
$container = Email_Editor_Container::container();

// Get renderer and personalizer services
$renderer = $container->get( Renderer::class );
$personalizer = $container->get( Personalizer::class );

// Render the email template
$post = get_post( $email_post_id );
$rendered_email = $renderer->render(
    $post,
    'Order Confirmation',
    'Your order has been confirmed and is being processed',
    'en'
);

// Create context for personalization
$context = [
    'order'           => $wc_order,         // WooCommerce order object
    'wp_user'         => $user,             // WordPress user object
    'recipient_email' => $customer_email,   // Recipient's email address
    'wc_email'        => $email_object,     // WooCommerce email object
];

// Set context and personalize the HTML content
$personalizer->set_context( $context );
$personalized_html = $personalizer->personalize_content( $rendered_email['html'] );

// The personalized HTML is now ready for sending
$email_html = $personalized_html;
$email_text = $personalizer->personalize_content( $rendered_email['text'], Personalizer::RENDERING_CONTEXT_TEXT );
```

This workflow first renders the email template with blocks, then applies personalization tags to the rendered HTML content, creating a fully personalized email ready for delivery.

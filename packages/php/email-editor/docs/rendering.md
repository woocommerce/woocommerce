# Email Rendering

**The email renderer classes** are designed to render WordPress posts containing block-based content (saved in the Gutenberg editor) as HTML and plain-text suitable for email delivery. These classes provide the core functionality for converting block editor content into email-compatible formats.

The email rendering system includes **Core Blocks Integration** that provides dedicated renderers for WordPress core blocks. This integration is essential for generating email client compatible HTML output - without these block-specific renderers, the rendered HTML would not be suitable for email clients.

## Table of Contents

- [Retrieving Services via DI Container](#retrieving-services-via-di-container)
- [Bootstrapping](#bootstrapping)
- [Renderer Classes](#renderer-classes)
    - [Renderer](#renderer)
    - [Content\_Renderer](#content_renderer)
- [Core Blocks Integration](#core-blocks-integration)
- [Integration Example](#integration-example)

## Retrieving Services via DI Container

The easiest way to access the rendering services is via DI container. The `Automattic\WooCommerce\EmailEditor\Email_Editor_Container` class provides a dependency injection container that can be used to easily obtain renderer services.

Here's how to obtain a renderer service:

```php
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Content_Renderer;

// Get the container instance
$container = Email_Editor_Container::container();

// Obtain renderer services
$renderer = $container->get( Renderer::class );
$content_renderer = $container->get( Content_Renderer::class );
```

## Bootstrapping

The rendering engine requires bootstrapping using the `Automattic\WooCommerce\EmailEditor\Bootstrap` class and its `init` method.

This bootstrap process registers necessary action callbacks. It must be called before the WordPress `init` action is triggered at or before the `plugins_loaded` action. This early initialization is required because the bootstrap hooks into core blocks registration, which occurs before the `init` hook.

**Example:**

```php
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Bootstrap;

// Get the container instance
$container = Email_Editor_Container::container();

// Get the Bootstrap service and initialize
$bootstrap = $container->get( Bootstrap::class );
$bootstrap->init();
```

## Renderer Classes

### Renderer

The `Automattic\WooCommerce\EmailEditor\Engine\Renderer\Renderer` class is responsible for rendering full HTML documents, including meta information in the head section and content in the body tags. This class provides a complete email template structure.

**Main Method:**

```php
/**
 * Renders the email template
 *
 * @param \WP_Post $post Post object.
 * @param string   $subject Email subject.
 * @param string   $pre_header An email preheader or preview text is the short snippet of text that follows the subject line in an inbox. See https://kb.mailpoet.com/article/418-preview-text
 * @param string   $language Email language.
 * @param string   $meta_robots Optional string. Can be left empty for sending, but you can provide a value (e.g. noindex, nofollow) when you want to display email html in a browser.
 * @param string   $template_slug Optional block template slug used for cases when email doesn't have associated template.
 * @return array
 */
public function render(
    \WP_Post $post,
    string $subject,
    string $pre_header, 
    string $language = 'en',
    string $meta_robots = '', 
    string $template_slug = ''
): array
```

**Returns:** An array containing:

-   `html`: The complete HTML email content
-   `text`: The plain text version of the email

**Example Usage:**

```php
$post           = get_post( $post_id );

// Post has an associated block template or a fallback blank template will be used, which renders only the post contents
$rendered_email = $renderer->render(
    $post,
    'Order Confirmation',
    'Your order has been confirmed',
    'en'
);

// Template is specified explicitly by passing template slug string
$rendered_email = $renderer->render(
    $post,
    'Order Confirmation',
    'Your order has been confirmed',
    'en',
    '',
    'my-email-template-slug'
);

$html_content = $rendered_email['html'];
$text_content = $rendered_email['text'];
```

### Content_Renderer

The `Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Content_Renderer` class is responsible for rendering only the HTML of block template content and a post. The block template has to contain a `core/post-content` block.

**Main Method:**

```php
/**
 * Render the content
 *
 * @param WP_Post           $post Post object.
 * @param WP_Block_Template $template Block template.
 * @return string
 */
public function render(
    WP_Post $post,
    WP_Block_Template $template
): string
```

**Returns:** A string containing the rendered HTML content

**Example Usage:**

```php
$post        = get_post( $post_id );
$template_id = get_stylesheet() . '//' . $template_slug;
$template    = get_block_template( $template_id );
$content     = $content_renderer->render( $post, $template );
```

## Core Blocks Integration

The package provides specialized renderers for the most commonly used WordPress core blocks, with plans to eventually cover all core blocks. These individual block renderers are located in the [packages/php/email-editor/src/Integrations/Core/Renderer/Blocks](https://github.com/woocommerce/woocommerce/tree/trunk/packages/php/email-editor/src/Integrations/Core/Renderer/Blocks) directory.

**Usage:**
The block renderers for core blocks are linked to the core blocks when they are registered, which happens very early (e.g. from a `plugins_loaded` callback), so the Core Blocks integration needs to be initialized early.

If you use the `Automattic\WooCommerce\EmailEditor\Bootstrap` class, the core integration is set up for you. In case you want to set manually, see the `Automattic\WooCommerce\EmailEditor\Bootstrap` init method.

## Integration Example

Here's how these classes work together in a typical email rendering workflow:

```php
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Bootstrap;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Renderer;

// Get services from container
$container = Email_Editor_Container::container();

// Bootstrap the rendering engine (must be called before WordPress init action)
$bootstrap = $container->get( Bootstrap::class );
$bootstrap->init();

// Rendering an email from a post

// Get renderer services
$renderer = $container->get( Renderer::class );

// Render a complete email
$post = get_post( $email_post_id );
$email_data = $renderer->render(
    $post,
    'Welcome to our store!',
    'Thank you for your purchase',
    'en'
);
```

This allows for flexible email rendering where you can either get the complete email document or just the content blocks, depending on your specific needs.


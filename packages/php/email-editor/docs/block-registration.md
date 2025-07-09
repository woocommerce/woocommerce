# Block Registration for Email Editor

This guide explains how to register WordPress blocks to support email rendering in the WooCommerce Email Editor. The email editor extends WordPress Gutenberg blocks with email-specific rendering capabilities, allowing blocks to generate HTML that is compatible with email clients.

## Table of Contents

-   [Overview](#overview)
-   [Block Configuration](#block-configuration)
    -   [Email Support Property](#email-support-property)
    -   [Email Render Callback](#email-render-callback)
-   [Registering Blocks for Email Support](#registering-blocks-for-email-support)
    -   [Method 1: Using block_type_metadata_settings Filter](#method-1-using-block_type_metadata_settings-filter)
    -   [Method 2: Direct Block Registration](#method-2-direct-block-registration)
-   [Creating Email Block Renderers](#creating-email-block-renderers)
    -   [Abstract Block Renderer](#abstract-block-renderer)
    -   [Rendering Context](#rendering-context)
    -   [Table Wrapper Helper](#table-wrapper-helper)
-   [Complete Example](#complete-example)
-   [Best Practices](#best-practices)
-   [Troubleshooting](#troubleshooting)

## Overview

The WooCommerce Email Editor allows WordPress blocks to be rendered in email-compatible HTML. To make a block compatible with email rendering, you need to:

1. **Enable email support** by setting `supports.email = true` in the block configuration
2. **Provide an email render callback** that generates email-compatible HTML
3. **Implement the rendering logic** that converts block content to email-friendly markup

The email editor automatically detects blocks with email support and uses their custom render callbacks instead of the default WordPress block rendering.

## Block Configuration

### Email Support Property

To indicate that a block supports email rendering, set the `email` property to `true` in the block's `supports` configuration:

```php
$block_settings = array(
    'name'     => 'my-plugin/custom-block',
    'title'    => 'Custom Block',
    'supports' => array(
        'email' => true,  // Enable email support
        // ... other supports
    ),
    // ... other settings
);
```

### Email Render Callback

Provide a custom render callback specifically for email rendering:

```php
$block_settings = array(
    'name'                  => 'my-plugin/custom-block',
    'render_email_callback' => array( $this, 'render_block_for_email' ),
    // ... other settings
);
```

The render callback should accept three parameters:

- `string $block_content` - The original block HTML content
- `array $parsed_block` - The parsed block data including attributes
- `Rendering_Context $rendering_context` - Email rendering context

## Registering Blocks for Email Support

### Method 1: Using block_type_metadata_settings Filter

The recommended approach is to add the `supports.email` to the block.json file or the JS client-side registration.

Another approach is to use the `block_type_metadata_settings` filter to modify block settings during registration:

```php
/**
 * Add email support to custom blocks.
 */
function add_email_support_to_blocks( $settings, $metadata ) {
    // List of blocks that should support email rendering
    $email_supported_blocks = array(
        'my-plugin/custom-block',
        'my-plugin/another-block',
    );

    if ( in_array( $settings['name'], $email_supported_blocks, true ) ) {
        // Enable email support
        $settings['supports']['email'] = true;
        
        // Set email render callback
        $settings['render_email_callback'] = array( 
            'My_Plugin_Email_Renderer', 
            'render_block' 
        );
    }

    return $settings;
}
add_filter( 'block_type_metadata_settings', 'add_email_support_to_blocks', 10, 2 );
```

### Method 2: Direct Block Registration

You can also register blocks directly with email support:

```php
/**
 * Register a custom block with email support.
 */
function register_custom_email_block() {
    register_block_type( 'my-plugin/custom-block', array(
        'title'       => __( 'Custom Email Block', 'my-plugin' ),
        'description' => __( 'A custom block with email support.', 'my-plugin' ),
        'category'    => 'widgets',
        'supports'    => array(
            'email' => true,
            // ... other supports
        ),
        'attributes'  => array(
            'content' => array(
                'type'    => 'string',
                'default' => '',
            ),
            // ... other attributes
        ),
        'render_email_callback' => 'my_plugin_render_email_block',
    ) );
}
add_action( 'init', 'register_custom_email_block' );
```

## Creating Email Block Renderers

### Abstract Block Renderer

For more complex blocks, you can create a dedicated renderer class that extends the abstract block renderer:

```php
<?php

namespace My_Plugin\Email\Renderer;

use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Abstract_Block_Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Email renderer for custom block.
 */
class Custom_Block_Renderer extends Abstract_Block_Renderer {

    /**
     * Render the block content for email.
     *
     * @param string            $block_content Block content.
     * @param array             $parsed_block Parsed block data.
     * @param Rendering_Context $rendering_context Rendering context.
     * @return string
     */
    public function render( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
        $attributes = $parsed_block['attrs'] ?? array();
        
        // Extract block attributes
        $content    = $attributes['content'] ?? '';
        $text_color = $attributes['textColor'] ?? '';
        $bg_color   = $attributes['backgroundColor'] ?? '';
        
        // Build email-compatible HTML
        $styles = array();
        if ( $text_color ) {
            $styles[] = 'color: ' . esc_attr( sanitize_hex_color( $text_color ) );
        }
        if ( $bg_color ) {
            $styles[] = 'background-color: ' . esc_attr( sanitize_hex_color( $bg_color ) );
        }
        
        $style_attr = ! empty( $styles ) ? ' style="' . implode( '; ', $styles ) . '"' : '';
        
        $html = sprintf(
            '<div%s>%s</div>',
            $style_attr,
            wp_kses_post( $content )
        );
        
        // Wrap in email-compatible table structure
        return Table_Wrapper_Helper::render_table_wrapper( $html );
    }
}
```

### Rendering Context

The `Rendering_Context` object provides access to theme settings and other rendering context:

```php
public function render( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
    // Get theme settings
    $theme_settings = $rendering_context->get_theme_settings();
    
    // Theme style values
    $theme_styles = $rendering_context->get_theme_styles();

    // Get the theme json
    $theme_json = $rendering_context->get_theme_json();
    
    // Use theme values in your rendering logic
    // ...
    
    return $rendered_html;
}
```

### Table Wrapper Helper

Email clients have limited CSS support, so using table-based layouts is recommended. The `Table_Wrapper_Helper` provides utilities for creating email-compatible table structures:

```php
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

// Basic table wrapper
$html = Table_Wrapper_Helper::render_table_wrapper( $content );

// Table wrapper with custom attributes
$html = Table_Wrapper_Helper::render_table_wrapper(
    $content,
    array( 'width' => '100%' ),           // Table attributes
    array( 'style' => 'padding: 20px;' ), // Cell attributes
    array(),                              // Row attributes
    true                                  // Render cell wrapper
);
```

## Complete Example

Here's a complete example of registering a custom block with email support:

```php
<?php
/**
 * Plugin Name: Custom Email Block
 * Description: A newsletter signup block with email editor support
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: my-plugin
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Custom Email Block Plugin
 */
class My_Custom_Email_Block {

    public function __construct() {
        add_action( 'init', array( $this, 'register_block' ) );
    }

    /**
     * Register the custom block using direct registration.
     */
    public function register_block() {
        // Enqueue the block JavaScript
        wp_register_script(
            'newsletter-signup-block',
            plugin_dir_url( __FILE__ ) . 'simple-block.js',
            array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
            '1.0.0'
        );

        // Register the block type
        register_block_type( 'my-plugin/newsletter-signup', array(
            'editor_script'   => 'newsletter-signup-block',
            'render_callback' => array( $this, 'render_frontend_block' ),
            'render_email_callback' => array( $this, 'render_email_block' ),
        ) );
    }

    /**
     * Render the block for frontend (non-email contexts).
     */
    public function render_frontend_block( $attributes ) {
        $title       = $attributes['title'] ?? 'Subscribe to our newsletter';
        $description = $attributes['description'] ?? 'Get the latest updates delivered to your inbox.';
        $button_text = $attributes['buttonText'] ?? 'Subscribe';
        $button_url  = $attributes['buttonUrl'] ?? '';

        $html = '<div class="newsletter-signup-block">';
        $html .= '<h3>' . esc_html( $title ) . '</h3>';
        $html .= '<p>' . esc_html( $description ) . '</p>';

        if ( $button_url && $button_text ) {
            $html .= '<a href="' . esc_url( $button_url ) . '">' . esc_html( $button_text ) . '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render the block for email.
     */
    public function render_email_block( $block_content, $parsed_block, $rendering_context ) {
        $attributes = $parsed_block['attrs'] ?? array();

        $title       = $attributes['title'] ?? 'Default Email Title';
        $description = $attributes['description'] ?? 'Default Email: Get the latest updates delivered to your inbox.';
        $button_text = $attributes['buttonText'] ?? 'Default Email: Subscribe';
        $button_url  = $attributes['buttonUrl'] ?? '';

        // Create email-compatible HTML
        $html = '<table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%;">
            <tr>
                <td style="padding: 20px; text-align: center; background-color: #f8f9fa;">
                    <h2 style="margin: 0 0 15px 0; font-family: Arial, sans-serif; color: #333;">' . esc_html( $title ) . '</h2>
                    <p style="margin: 0 0 20px 0; font-family: Arial, sans-serif; color: #666; line-height: 1.5;">' . esc_html( $description ) . '</p>';

        if ( $button_url && $button_text ) {
            $html .= '<a href="' . esc_url( $button_url ) . '" style="display: inline-block; padding: 12px 24px; background-color: #007cba; color: #ffffff; text-decoration: none; border-radius: 4px; font-family: Arial, sans-serif;">' . esc_html( $button_text ) . '</a>';
        }

        $html .= '</td>
            </tr>
        </table>';

        return $html;
    }
}

// Initialize the plugin
new My_Custom_Email_Block();
```

JS

```javascript
/**
 * Simple test version of the newsletter block
 */
(function() {
    console.log('Newsletter block JavaScript is loading...');

    // Check if WordPress dependencies are available
    if (typeof wp === 'undefined') {
        console.error('WordPress dependencies not available');
        return;
    }

    const { __ } = wp.i18n;
    const { createElement } = wp.element;
    const { registerBlockType } = wp.blocks;
    const { useBlockProps } = wp.blockEditor;

    console.log('Registering newsletter-signup block...');

    registerBlockType('my-plugin/newsletter-signup', {
        title: __('Newsletter Signup', 'my-plugin'),
        icon: 'email-alt',
        category: 'widgets',
        description: __('A newsletter signup form for emails.', 'my-plugin'),
		supports: {
			email: true, // required for the email editor.
		},
        attributes: {
            title: {
                type: 'string',
                default: 'Subscribe to our newsletter'
            }
        },

        edit: function(props) {
            const blockProps = useBlockProps();

            return createElement(
                'div',
                blockProps,
                createElement(
                    'div',
                    {
                        style: {
                            padding: '20px',
                            border: '1px solid #ccc',
                            textAlign: 'center'
                        }
                    },
                    createElement('h3', null, props.attributes.title),
                    createElement('p', null, 'Newsletter signup block - working!')
                )
            );
        },

        save: function() {
            return null; // Server-side rendering
        }
    });

    console.log('Newsletter signup block registered successfully!');
})();
```

## Best Practices

### Email-Compatible HTML

1. **Use table-based layouts** - Email clients have inconsistent CSS support
2. **Inline CSS styles** - External stylesheets are often blocked
3. **Avoid complex CSS** - Stick to basic properties like color, background-color, padding
4. **Use web-safe fonts** - Arial, Helvetica, Times New Roman, etc.
5. **Test across email clients** - Use tools like Litmus or Email on Acid

### Block Design

1. **Keep it simple** - Complex layouts may not render consistently
2. **Use semantic HTML** - Helps with accessibility and rendering
3. **Provide fallbacks** - Graceful degradation for unsupported features
4. **Consider mobile** - Many emails are read on mobile devices

### Code Quality

1. **Escape output** - Use `esc_html()`, `esc_url()`, `wp_kses_post()` appropriately
2. **Validate input** - Check attributes and provide sensible defaults
3. **Handle errors gracefully** - Return original content if rendering fails
4. **Follow WordPress coding standards** - Consistent code style

## Troubleshooting

### Block Not Rendering in Email

1. **Check email support** - Ensure `supports.email` is set to `true`
2. **Verify callback** - Confirm `render_email_callback` is properly set
3. **Hook timing** - Make sure filters are added before block registration
4. **Check block name** - Ensure the block name matches exactly

### Email Display Issues

1. **Test in multiple clients** - Different email clients handle HTML differently
2. **Validate HTML** - Use an HTML validator to check for errors
3. **Check CSS support** - Some CSS properties are not supported in emails
4. **Use tables for layout** - More reliable than CSS flexbox/grid in emails

### Debug Information

Enable debug logging to troubleshoot issues:

```php
// Add this to wp-config.php for debugging
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );

// The email editor logs to the WordPress debug log
```

Check the WordPress debug log (`/wp-content/debug.log`) for email editor related messages. 

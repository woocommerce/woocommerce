# Site Style Sync Hooks and Filters

The WooCommerce Email Editor Site Style Sync feature provides several hooks and filters that developers can use to customize the synchronization behavior.

## Filters

### `woocommerce_email_editor_synced_site_styles`

Filter the synced site style data before applying to email theme.

```php
/**
 * Filter the synced site style data
 *
 * @since 1.1.0
 * @param array $synced_data The converted email-compatible theme data
 * @param array $site_data The original site theme data
 * @return array Modified synced data
 */
apply_filters( 'woocommerce_email_editor_synced_site_styles', $synced_data, $site_data );
```

**Example Usage:**

```php
add_filter( 'woocommerce_email_editor_synced_site_styles', function( $synced_data, $site_data ) {
    // Override specific colors for emails
    if ( isset( $synced_data['styles']['color'] ) ) {
        $synced_data['styles']['color']['background'] = '#ffffff'; // Force white background
        $synced_data['styles']['color']['text'] = '#333333'; // Force dark text for readability
    }
    
    // Add custom email-specific font family
    if ( isset( $synced_data['settings']['typography']['fontFamilies'] ) ) {
        $synced_data['settings']['typography']['fontFamilies'] = array(
            'slug' => 'email-custom',
            'name' => 'Email Custom Font',
            'fontFamily' => 'Arial, sans-serif'
        );
    }
    
    return $synced_data;
}, 10, 2
 );
```

### `woocommerce_email_editor_site_style_sync_enabled`

Control whether site style sync functionality is enabled.

```php
/**
 * Filter to enable/disable site style sync functionality
 *
 * @since 1.1.0
 * @param bool $enabled Whether site style sync is enabled
 * @return bool
 */
apply_filters( 'woocommerce_email_editor_site_style_sync_enabled', true );
```

**Example Usage:**

```php
// Disable site style sync for specific themes
add_filter( 'woocommerce_email_editor_site_style_sync_enabled', function( $enabled ) {
    $current_theme = get_template();
    $incompatible_themes = array( 'legacy-theme', 'custom-theme' );
    
    if ( in_array( $current_theme, $incompatible_themes ) ) {
        return false;
    }
    
    return $enabled;
});
```

### `woocommerce_email_editor_site_theme`

Filter the site theme data used for email style sync.

This filter allows overriding the site theme used when syncing global styles for the email editor. Useful for environments where theme data is not directly accessible (e.g., wp.com Atomic and Jetpack sites).

```php
/**
 * Filter the site theme data used for email style sync.
 *
 * @since 2.3.0
 * @param WP_Theme_JSON $site_theme The site theme data.
 * @return WP_Theme_JSON Modified site theme data.
 */
apply_filters( 'woocommerce_email_editor_site_theme', $site_theme );
```

**Example Usage:**

```php
// Override the site theme with custom theme data for environments
// where theme data is not directly accessible
add_filter( 'woocommerce_email_editor_site_theme', function( $site_theme ) {
    // Fetch theme data from an external source
    $custom_theme_data = get_custom_theme_data();

    if ( $custom_theme_data ) {
        return new WP_Theme_JSON( $custom_theme_data, 'custom' );
    }

    return $site_theme;
});
```

## Advanced Customization Examples

### Custom Font Mapping

```php
add_filter( 'woocommerce_email_editor_synced_site_styles', function( $synced_data, $site_data ) {
    // Map specific site fonts to preferred email fonts
    $font_mappings = array(
        'Inter' => 'Arial, sans-serif',
        'Playfair Display' => 'Georgia, serif',
        'Roboto Mono' => '"Courier New", monospace',
    );
    
    if ( isset( $synced_data['styles']['typography']['fontFamily'] ) ) {
        $current_font = $synced_data['styles']['typography']['fontFamily'];
        
        foreach ( $font_mappings as $site_font => $email_font ) {
            if ( strpos( $current_font, $site_font ) !== false ) {
                $synced_data['styles']['typography']['fontFamily'] = $email_font;
                break;
            }
        }
    }
    
    return $synced_data;
}, 10, 2 );
```

### Conditional Sync Based on Email Type

```php
add_filter( 'woocommerce_email_editor_synced_site_styles', function( $synced_data, $site_data ) {
    global $post;
    
    // Different styling for different email types
    if ( $post && get_post_meta( $post->ID, 'email_type', true ) === 'promotional' ) {
        // Use brand colors for promotional emails
        $synced_data['styles']['color']['background'] = '#f8f9fa';
        $synced_data['styles']['elements']['button']['color']['background'] = '#007cba';
    } elseif ( $post && get_post_meta( $post->ID, 'email_type', true ) === 'transactional' ) {
        // Use neutral colors for transactional emails
        $synced_data['styles']['color']['background'] = '#ffffff';
        $synced_data['styles']['elements']['button']['color']['background'] = '#666666';
    }
    
    return $synced_data;
}, 10, 2 );
```

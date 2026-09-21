---
post_title: Integrating with coming soon mode

---

# Integrating with coming soon mode

This guide provides examples for third-party developers and hosting providers on how to integrate their systems with WooCommerce's coming soon mode. For more details, please read the [developer blog post](https://developer.woocommerce.com/2024/06/18/introducing-coming-soon-mode/). For site visibility settings, please refer to the [admin documentation](https://woocommerce.com/document/configuring-woocommerce-settings/coming-soon-mode/).

## Introduction

WooCommerce's coming soon mode allows you to temporarily make your site invisible to the public while you work on it. This guide will show you how to integrate this feature with your system, clear server cache when site visibility settings change, and sync coming soon mode with other plugins.

## Prerequisites

-   Familiarity with PHP and WordPress development.

## Understanding site visibility states

Site visibility is stored in two options:

| Option | Values | Meaning |
| --- | --- | --- |
| `woocommerce_coming_soon` | `yes` / `no` | Whether coming soon mode is active. |
| `woocommerce_store_pages_only` | `yes` / `no` | When coming soon mode is active, whether it applies only to store pages. Has no effect when `woocommerce_coming_soon` is `no`. |

Together they describe three states:

| State | `woocommerce_coming_soon` | `woocommerce_store_pages_only` | What visitors see |
| --- | --- | --- | --- |
| Live | `no` | (no effect) | The whole site. |
| Coming soon, entire site | `yes` | `no` | A coming soon page everywhere. |
| Coming soon, store pages only | `yes` | `yes` | A coming soon page on store pages; the rest of the site stays public. |

WooCommerce sets `woocommerce_store_pages_only` to `yes` when it installs, and onboarding leaves it at `yes` on sites that already have content. So a `yes` here is usually the default rather than something the merchant picked.

Two more options decide who can get past a coming soon page:

| Option | Effect |
| --- | --- |
| `woocommerce_private_link` | When `yes`, a visitor who arrives with the share key in a `woo-share` query parameter sees the real site. A cookie keeps that access for 90 days. |
| `woocommerce_share_key` | The key that grants that access. |

Two more things affect whether anyone sees a coming soon page at all. WooCommerce only enforces coming soon mode while the `launch-your-store` feature is enabled, so with that feature off these options are stored but nothing is hidden. Users who can `manage_woocommerce` always see the real site, so check your work in a logged-out browser.

Most external systems, including many hosting dashboards, treat visibility as a single on/off switch. `woocommerce_coming_soon` on its own does not tell you the whole state, so read [Syncing when the states do not match](#syncing-when-the-states-do-not-match) before integrating.

## Step-by-step instructions

### Clear server cache on site visibility settings change

When the site's visibility settings change, it may be necessary to clear a server cache to apply the changes and re-cache customer-facing pages. The [`update_option`](https://developer.wordpress.org/reference/hooks/update_option/) hook can be used to achieve this.

```php
add_action( 'update_option_woocommerce_coming_soon', 'clear_server_cache', 10, 3 );
add_action( 'update_option_woocommerce_store_pages_only', 'clear_server_cache', 10, 3 );

function clear_server_cache( $old_value, $new_value, $option ) {
    // Implement your logic to clear the server cache.
    if ( function_exists( 'your_cache_clear_function' ) ) {
        your_cache_clear_function();
    }
}
```

### Clear server cache on template changes

By default, Coming-soon pages are set with `Cache-Control: max-age=60` header. This setting enables CDNs and other caching mechanisms to cache the page for 60 seconds, balancing the need for efficient performance with reasonable update times.

When the user changes the coming soon template, it's recommended that any cache be purged so the changes take effect immediately when the client-side cache expires.

You can use the `save_post_wp_template`, `save_post_wp_template_part`, and `save_post_wp_global_styles` hooks to detect when a template is updated and trigger the cache purge.

```php
add_action( 'save_post_wp_template', 'purge_cache_on_template_change', 10, 3 );
add_action( 'save_post_wp_template_part', 'purge_cache_on_template_change', 10, 3 );
add_action( 'save_post_wp_global_styles', 'purge_cache_on_template_change', 10, 3 );

function purge_cache_on_template_change( $post_id, $post, $update ) {
    // Check if the template is associated with the coming soon mode.
    if ( 'coming-soon' === $post->post_name ) {
        // Implement your logic to clear the server cache.
        if ( function_exists( 'your_cache_clear_function' ) ) {
            your_cache_clear_function();
        }
    }
}
```

### Syncing coming soon mode with other plugins

The coming soon mode can be programmatically synced from a plugin or application. Here are some example use cases:

-   Integrating with a maintenance mode plugin.
-   Integrating with a hosting provider's coming soon mode.

#### Trigger from WooCommerce

You can use the following example to run a code such as setting your plugin's status when coming soon mode option is updated:

```php
add_action( 'update_option_woocommerce_coming_soon', 'sync_coming_soon_to_other_plugins', 10, 3 );

function sync_coming_soon_to_other_plugins( $old_value, $new_value, $option ) {
    $is_enabled = $new_value === 'yes';

    // Implement your logic to sync coming soon status.
    if ( function_exists( 'your_plugin_set_coming_soon' ) ) {
        your_plugin_set_coming_soon( $is_enabled );
    }
}
```

This example reports coming soon mode as a single on/off value, so it cannot tell the whole site apart from store pages only. If your system needs that distinction, hook `update_option_woocommerce_store_pages_only` as well. If it cannot represent it, see [Syncing when the states do not match](#syncing-when-the-states-do-not-match).

It also syncs on every change without looking at what state your own system is in. If your system has a visibility state WooCommerce cannot express, such as one that limits who may view the site, this will overwrite it. See [Syncing when the states do not match](#syncing-when-the-states-do-not-match).

#### Trigger from other plugins

You can use the following example to enable or disable WooCommerce coming soon mode from another plugin by directly updating `woocommerce_coming_soon` option:

```php
function sync_coming_soon_from_other_plugins( $is_enabled ) {
    // Check user capability.
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have sufficient permissions to access this page.' );
    }

    // Set coming soon mode. This control says nothing about how much of the site to
    // hide, so 'woocommerce_store_pages_only' is left alone. See "Syncing when the
    // states do not match" below.
    if ( isset( $is_enabled ) ) {
        update_option( 'woocommerce_coming_soon', $is_enabled ? 'yes' : 'no' );
    }
}
```

#### 2-way sync with plugins

If 2-way sync is needed, use the following example where `update_option` will not recursively call `sync_coming_soon_from_other_plugins`:

```php
add_action( 'update_option_woocommerce_coming_soon', 'sync_coming_soon_to_other_plugins', 10, 3 );

function sync_coming_soon_to_other_plugins( $old_value, $new_value, $option ) {
    $is_enabled = $new_value === 'yes';

    // Implement your logic to sync coming soon status.
    if ( function_exists( 'your_plugin_set_coming_soon' ) ) {
        your_plugin_set_coming_soon( $is_enabled );
    }
}

function sync_coming_soon_from_other_plugins( $is_enabled ) {
    // Check user capability.
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have sufficient permissions to access this page.' );
    }

    if ( isset( $is_enabled ) ) {
        // Temporarily remove the action to prevent a recursive call.
        remove_action( 'update_option_woocommerce_coming_soon', 'sync_coming_soon_to_other_plugins', 10, 3 );

        // Set coming soon mode.
        update_option( 'woocommerce_coming_soon', $is_enabled ? 'yes' : 'no' );

        // Re-add the action.
        add_action( 'update_option_woocommerce_coming_soon', 'sync_coming_soon_to_other_plugins', 10, 3 );
    }
}
```

#### One-way binding with option override

We could also programmatically bind the coming soon option from another plugin by overriding the `woocommerce_coming_soon` option. This is advantageous since it simplifies state management and prevents possible out-of-sync issues.

In the following example, we're binding the coming soon option from another plugin by overriding the `woocommerce_coming_soon` option.

If your system has no equivalent of "Apply to store pages only", override `woocommerce_store_pages_only` as well.

Overriding `woocommerce_coming_soon` on its own leaves the stored `woocommerce_store_pages_only` in effect, and that is usually `yes`. Your coming soon state would then hide the store pages and leave the rest of the site public.

```php
add_filter( 'pre_option_woocommerce_coming_soon', 'override_option_woocommerce_coming_soon' );

function override_option_woocommerce_coming_soon( $current_value ) {
    // Implement your logic to sync coming soon status.
    if ( function_exists( 'your_plugin_is_coming_soon' ) ) {
        return your_plugin_is_coming_soon() ? 'yes' : 'no';
    }
    return $current_value;
}

add_filter( 'pre_option_woocommerce_store_pages_only', 'override_option_woocommerce_store_pages_only' );

function override_option_woocommerce_store_pages_only( $current_value ) {
    // While your system is driving visibility, coming soon mode covers the entire site.
    if ( function_exists( 'your_plugin_is_coming_soon' ) && your_plugin_is_coming_soon() ) {
        return 'no';
    }
    return $current_value;
}

add_filter( 'pre_update_option_woocommerce_coming_soon', 'override_update_woocommerce_coming_soon', 10, 2 );

function override_update_woocommerce_coming_soon( $new_value, $old_value ) {
    // Check user capability.
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have sufficient permissions to access this page.' );
    }

    // Implement your logic to sync coming soon status.
    if ( function_exists( 'your_plugin_set_coming_soon' ) ) {
        your_plugin_set_coming_soon( $new_value === 'yes' );
    }
}
```

Add these filters when your plugin file loads, as the example does. WooCommerce reads the options at `plugins_loaded` to decide whether to hook the coming soon template, so a filter added later, on `init` for instance, has no effect.

#### Syncing when the states do not match

Neither system has a single visibility switch, so start with what each one can say.

WooCommerce says two things: whether coming soon mode is on, and whether it covers the whole site or only the store pages. Your system probably says the first. It probably has nothing for the second. It may also have a state of its own that WooCommerce cannot store, such as private or members-only.

**Sync what both systems can say, and do not invent the rest.** This goes wrong in both directions, and both are the same mistake: filling in a value the other system never gave you. It fails quietly too. Nothing errors, neither settings screen says the two disagree, and you find out when a customer sees a page that was meant to be hidden.

**Reading.** `woocommerce_coming_soon` tells you coming soon mode is on, not how much of the site it covers. On a site set to "Apply to store pages only", everything outside the store is still public, so calling that site hidden would be wrong. Read both options:

```php
function your_plugin_get_woocommerce_visibility() {
    if ( 'yes' !== get_option( 'woocommerce_coming_soon' ) ) {
        return 'live';
    }

    return 'yes' === get_option( 'woocommerce_store_pages_only' )
        ? 'coming-soon-store-pages-only'
        : 'coming-soon-entire-site';
}
```

**Writing.** Write what your control actually says, and nothing more. Most integrations have one control that means "hide this whole site". That says both things at once, coming soon on and covering everything, so write both options:

```php
update_option( 'woocommerce_coming_soon', 'yes' );
update_option( 'woocommerce_store_pages_only', 'no' );
```

Writing only `woocommerce_coming_soon` would leave the stored `woocommerce_store_pages_only` in effect, and that is usually `yes`. A control that promised to hide the whole site would hide only the store.

Setting `woocommerce_store_pages_only` to `no` does overwrite the merchant's choice if they picked "Apply to store pages only" in WooCommerce. That is a real change to their settings, so tell them you made it. If you would rather leave what is stored untouched, use the override filters above instead.

Leave `woocommerce_store_pages_only` alone when your control says nothing about how much of the site to hide, such as a plain coming soon toggle:

```php
function your_plugin_set_woocommerce_visibility( $is_coming_soon ) {
    update_option( 'woocommerce_coming_soon', $is_coming_soon ? 'yes' : 'no' );

    // This control says nothing about how much of the site to hide, so the
    // merchant's own choice is left in place.
}
```

Either way, do not guess. A value you make up overwrites the merchant's choice, and a value you leave behind can hide less than you promised.

**A state WooCommerce cannot store.** The same rule protects your own states, but what to do depends on which kind of state you are in.

If yours is a pre-launch state, the same kind WooCommerce has, a merchant changing WooCommerce's settings is telling you something you can act on. Someone who turns on "Apply to store pages only" wants their store hidden and the rest of their site visible, and they cannot have that while you are hiding everything. Following them is what they asked for.

If yours is an access-control state, such as private or members-only, WooCommerce has nothing equivalent, and a change in WooCommerce tells you nothing about it. Say a site is private, the merchant changes a WooCommerce setting, and your sync handler fires. The site drops out of private into coming soon, which nobody asked for. Leave that state where the merchant put it. It also helps to show them which system is in charge, so WooCommerce's settings screen does not look like the whole story.

**Reporting visibility.** These mismatches are hard to spot because a status usually shows only part of the picture. If you show a merchant a single status, work it out from everything in force, show the strictest one, and say what is still public. A site labelled "Coming soon" with a readable blog sends people hunting for a bug that is not there.

### Custom exclusions filter

It is possible for developers to add custom exclusions that bypass the coming soon protection. This is useful for exclusions like always bypassing the screen on a specific IP address, or making a specific landing page available.

#### Disabling coming soon in all pages

If there is another feature that behaves similarly to WooCommerce's coming soon mode, it can cause unintended conflicts. The coming soon mode can be disabled by excluding all customer-facing pages. The following is an example:

```php
add_filter( 'woocommerce_coming_soon_exclude', function() {
    return true;
}, 10 );
```

#### Disabling coming soon except for a specific page

Use the following example to exclude a certain page based on the page's ID. Replace `<page-id>` with your page identifier:

```php
add_filter( 'woocommerce_coming_soon_exclude', function( $is_excluded ) {
    if ( get_the_ID() === <page-id> ) {
        return true;
    }
    return $is_excluded;
}, 10 );
```

#### Custom share links

The following example shows how to integrate with a custom share code. We recommend using cookies or other storage to persist the access when users navigate across the site:

```php
add_filter( 'woocommerce_coming_soon_exclude', function( $exclude ) {
    // Implement your logic to get and validate share code.
    if ( function_exists( 'your_plugin_get_share_code' ) && function_exists( 'your_plugin_is_valid_share_code' ) ) {
        $share_code = your_plugin_get_share_code();
        if ( your_plugin_is_valid_share_code( $share_code ) ) {
            return true;
        }
    }

    return $exclude;
} );
```

### Extend "Apply to store pages only" setting

When using the `Apply to store pages only` setting, you may want to add a custom page to the list of store pages which will be restricted by coming soon mode. You can use the following example to add a custom page:

```php
add_filter( 'woocommerce_store_pages', function( $pages ) {
    $page = get_page_by_path( 'your-page-slug' );
    if ( $page ) {
        $pages[] = $page->ID;
    }
    return $pages;
} );
```

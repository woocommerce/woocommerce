---
post_title: Global styles for WooCommerce
sidebar_label: Global styles
sidebar_position: 3
---

# Global styles for WooCommerce

Global styles let a block theme define typography, color, borders, and spacing in `theme.json`. WordPress applies those choices wherever a matching block appears, and merchants can customize supported settings in the Site Editor.

WooCommerce blocks participate in the same system as core WordPress blocks. Support varies by block, however, and WooCommerce templates sometimes use a core block in a product-specific context. Before adding a rule, confirm both the block name and the styles that block supports.

## Requirements

This guide assumes you have:

- A block theme with a `theme.json` file. The examples use `theme.json` version 3, introduced in WordPress 6.6.
- A current, supported version of WooCommerce. Block names and supported settings can differ in earlier releases.
- Familiarity with the [`theme.json` styles structure](https://developer.wordpress.org/themes/global-settings-and-styles/styles/).
- Access to the Site Editor under **Appearance > Editor**.

## How global styles apply to WooCommerce blocks

Block-specific styles belong under `styles.blocks` in `theme.json`. The key is the block's full namespace and name:

```json
{
	"$schema": "https://schemas.wp.org/trunk/theme.json",
	"version": 3,
	"styles": {
		"blocks": {
			"woocommerce/product-price": {
				"typography": {
					"fontSize": "1.25rem",
					"fontWeight": "600"
				}
			}
		}
	}
}
```

A block only responds to features declared in its registration. For example:

- `woocommerce/product-price` supports text and background color, typography, margin, and padding.
- `woocommerce/product-sale-badge` supports background color, gradients, typography, borders, and margin, but not text color.
- `woocommerce/product-collection` provides alignment and layout controls, but does not expose color, typography, or spacing support itself.
- `woocommerce/store-notices` exposes alignment but not color, typography, or spacing support.

Use the [WooCommerce block reference](/docs/block-development/reference/block-references/) as a starting point, then confirm the registered `supports` for the version running on your site. The WordPress REST API's block-types endpoint reports the runtime registration and is the most reliable source when versions differ.

### Core blocks in WooCommerce templates

Do not assume that every product element is a `woocommerce/*` block. WooCommerce uses core blocks when they already provide the required behavior.

The default blockified Single Product and product-grid templates render product titles with `core/post-title` plus WooCommerce-specific context attributes. Product categories, tags, and brands use `core/post-terms` variations. A custom template may instead use `woocommerce/product-title`, so inspect the template's block markup before choosing a selector.

This distinction also affects scope. A rule for `core/post-title` can style post titles outside WooCommerce. When a design should apply only to a product template, consider setting the style in that template's block attributes or registering a product-specific block style variation instead of applying a site-wide `core/post-title` rule.

## Style common product elements

### Product prices and summaries

Product Price and Product Summary both expose extensive typography controls. Product Summary also supports text alignment, text and background colors, margin, and padding.

```json
{
	"$schema": "https://schemas.wp.org/trunk/theme.json",
	"version": 3,
	"styles": {
		"blocks": {
			"woocommerce/product-price": {
				"color": {
					"text": "var(--wp--preset--color--contrast)"
				},
				"typography": {
					"fontSize": "var(--wp--preset--font-size--large)",
					"fontWeight": "700"
				}
			},
			"woocommerce/product-summary": {
				"typography": {
					"fontSize": "var(--wp--preset--font-size--medium)",
					"lineHeight": "1.6"
				}
			}
		}
	}
}
```

Use WooCommerce's currency settings for number and currency formatting. Global styles should control the price's visual treatment, not its value or format.

### Sale badges and product buttons

Product Sale Badge supports background color, gradients, borders, typography, and margin. The following example creates a pill-shaped badge:

```json
{
	"$schema": "https://schemas.wp.org/trunk/theme.json",
	"version": 3,
	"styles": {
		"blocks": {
			"woocommerce/product-sale-badge": {
				"color": {
					"background": "var(--wp--preset--color--accent-2)"
				},
				"border": {
					"radius": "999px",
					"width": "0"
				},
				"spacing": {
					"margin": {
						"right": "0.5rem",
						"top": "0.5rem"
					}
				}
			}
		}
	}
}
```

Product Button supports text and background colors, typography, border radius, margin, and padding:

```json
{
	"$schema": "https://schemas.wp.org/trunk/theme.json",
	"version": 3,
	"styles": {
		"blocks": {
			"woocommerce/product-button": {
				"color": {
					"background": "var(--wp--preset--color--contrast)",
					"text": "var(--wp--preset--color--base)"
				},
				"border": {
					"radius": "0.4rem"
				}
			}
		}
	}
}
```

The sale badge's label is content rather than presentation. To change it, use the `woocommerce_sale_badge_text` filter instead of `theme.json`:

```php
add_filter(
	'woocommerce_sale_badge_text',
	function ( $sale_text, $product ) {
		return __( 'Limited offer', 'your-textdomain' );
	},
	10,
	2
);
```

### Product grids

Product Collection owns the query and grid layout. Use its **Layout** controls to set columns and gaps. Style the blocks repeated inside each product card for the spacing between the image, title, price, and button.

```json
{
	"$schema": "https://schemas.wp.org/trunk/theme.json",
	"version": 3,
	"styles": {
		"blocks": {
			"woocommerce/product-image": {
				"spacing": {
					"margin": {
						"bottom": "0.75rem"
					}
				}
			},
			"woocommerce/product-price": {
				"spacing": {
					"margin": {
						"bottom": "0.5rem"
					}
				}
			}
		}
	}
}
```

The `woocommerce/product-template` wrapper supports color and typography, so it is useful for shared product-card text styles. Its layout controls are locked to preserve the grid structure.

## Customize block style variations

Block style variations are selectable visual presets for one block. They are distinct from block variations, which provide different attribute configurations, and from global style variations, which provide alternate designs for an entire theme.

Product Button registers two block style variations: **Fill** and **Outline**. Because both variations are registered by the block, a theme can customize them under `styles.blocks.woocommerce/product-button.variations`:

```json
{
	"$schema": "https://schemas.wp.org/trunk/theme.json",
	"version": 3,
	"styles": {
		"blocks": {
			"woocommerce/product-button": {
				"variations": {
					"outline": {
						"border": {
							"color": "var(--wp--preset--color--contrast)",
							"width": "2px"
						},
						"color": {
							"background": "transparent",
							"text": "var(--wp--preset--color--contrast)"
						}
					}
				}
			}
		}
	}
}
```

WordPress ignores variation styles whose names are not registered for the block. Check the block's **Styles** panel or its registration before relying on a variation.

### Register a custom block style variation

Register a custom variation in the theme's `functions.php` file or a plugin, then define its presentation in `theme.json`.

```php
function mytheme_register_product_button_styles() {
	register_block_style(
		'woocommerce/product-button',
		array(
			'name'  => 'rounded',
			'label' => __( 'Rounded', 'your-textdomain' ),
		)
	);
}
add_action( 'init', 'mytheme_register_product_button_styles' );
```

After registration, add a `rounded` entry beside `fill` or `outline` in the Product Button's `variations` object.

> [!NOTE]
> Replace `your-textdomain` with your theme or plugin's text domain.

## Create global style variations

Global style variations are alternate `theme.json` configurations for the entire theme. Store each variation as a JSON file in the theme's `/styles` directory:

```json
{
	"$schema": "https://schemas.wp.org/trunk/theme.json",
	"version": 3,
	"title": "Bold",
	"settings": {
		"color": {
			"palette": [
				{
					"color": "#1a1a1a",
					"name": "Contrast",
					"slug": "contrast"
				}
			]
		}
	},
	"styles": {}
}
```

Each file appears as a preset in the Site Editor's **Styles** panel, using the `title` value as its label. A variation can override any supported part of `theme.json`, including colors, typography, spacing, and block-specific styles.

When a merchant selects and saves a variation, WordPress stores that configuration as a user customization in the database. A later change to the variation file does not automatically replace the saved customization. Call out material style-variation changes in your theme's changelog.

## Customize styles in the Site Editor

The Site Editor exposes the same registered block supports through an interface. Open **Appearance > Editor > Styles**, then choose **Blocks** to browse blocks with available global style controls.

![The Blocks panel in the WordPress Site Editor showing WooCommerce blocks](_media/global-styles-blocks-panel.png)

Select a block in a template to work with controls for that specific instance. Use the global **Styles > Blocks** panel when the same choice should apply across the site.

The Site Editor is useful for prototyping against real product data. Once the design is settled, move the values into the theme's `theme.json` when they should ship as version-controlled defaults. Remember that saved user customizations take precedence over theme defaults.

## Export Site Editor changes

You can export the active theme together with Site Editor changes:

1. Open **Appearance > Editor**.
2. Open a template so the editing canvas and top toolbar are visible.
3. Open the three-dot **Options** menu in the top-right corner.
4. Under **Tools**, select **Export**.

![The Site Editor Options menu with Export under Tools](_media/global-styles-export-menu.png)

WordPress downloads a ZIP archive containing the theme's templates, template parts, and style settings. Review the exported files before adding them to version control, especially when the site contains merchant-specific template changes.

## Check your work

Block registrations and template composition can change between WordPress and WooCommerce versions. Before shipping a theme:

- Confirm block names by viewing the template through the Site Editor's **Code editor**.
- Check each block's runtime `supports` through the WordPress REST API's block-types endpoint.
- Verify registered block style variations in the block's **Styles** panel.
- Test the editor and front end, including product archives, a single product, Cart, and Checkout.
- Test a site with saved user styles to confirm the intended precedence between user and theme settings.

## References

- [WooCommerce blocks reference](/docs/block-development/reference/block-references/)
- [WordPress global settings and styles](https://developer.wordpress.org/themes/global-settings-and-styles/)
- [WordPress styles reference](https://developer.wordpress.org/themes/global-settings-and-styles/styles/styles-reference/)
- [WordPress block style variations](https://developer.wordpress.org/themes/features/block-style-variations/)
- [WordPress global style variations](https://developer.wordpress.org/themes/global-settings-and-styles/style-variations/)

## Next steps

- [Template hierarchy reference](/docs/theming/block-theme-development/template-hierarchy/)
- [Cart and Checkout blocks theming](/docs/theming/block-theme-development/cart-and-checkout/)
- [CSS styling for themes](/docs/theming/block-theme-development/css-styling/)

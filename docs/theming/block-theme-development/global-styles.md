---
post_title: 'Block theme development: Global styles for WooCommerce'
sidebar_label: Global styles
sidebar_position: 3
---

# Global styles for WooCommerce

Global styles let a Block theme define typography, color, and spacing once in theme.json, then apply those choices everywhere a matching Block appears. WooCommerce Blocks are a part of this system, but there's differing adoption between them. This means a few product elements render through core WordPress Blocks, which changes the selector you target.

This section will cover how global styles apply across a WooCommerce store through theme.json, starting with applying colors and typography. From there, it moves into using Block style variations, registering your own style variations, customizing the appearance in the Site Editor, and even exporting a finished style configuration.

## Requirements

Before you begin, make sure you have the following in place:

- **The latest version of WordPress.** This guide's examples target theme.json version 3.
- **A Block theme.** Global styles and the Site Editor only apply to themes built around theme.json and Block templates, not classic themes.
- **A current version of WooCommerce.** Block names, registered supports, and available style variations may be different in an earlier release. This guide reflects current Block registrations.
- **Familiarity with theme.json.** The guidance builds directly on the theme.json styles and settings structure rather than introducing it from scratch.

## How global styles apply to WooCommerce Blocks

Every Block that declares supports for color, typography, or spacing in its registration can be targeted from theme.json under styles.blocks. [WooCommerce Blocks](https://developer.woocommerce.com/docs/block-development/reference/block-references/) offer varied support.

For example, woocommerce/product-price supports color, typography, and spacing. woocommerce/product-collection has descendents that render a grid of products so supports none of the three. As such, look to confirm what the Block supports before writing style rules.

Also, some elements are actually core WordPress Blocks configured for a product context rather than dedicated WooCommerce Blocks:

- **Product titles** render through core/post-title inside the Single Product Block.
- **Product categories and tags** render through core/post-terms, configured with WooCommerce-specific taxonomy variations rather than a dedicated WooCommerce Block.
- **Product grids created with Product Collection** render each item's title the same way through core/post-title, when the template follows WooCommerce's own patterns.

WooCommerce builds its templates by mixing its own and core WordPress Blocks wherever the latter already does the job. The reliable way to confirm what you're styling is to check the Block markup on the template you're editing. This can either be through the **Code view** option in the Site Editor or by inspecting the template through the WordPress REST API's Block-types endpoint.

## Using theme.json to define WooCommerce-specific styles

Most of your styling work happens within theme.json. The next three sections cover typography for text-based product details, setting colors for on-screen elements, and spacing for product grids.

### Styling typography for product titles, prices, and descriptions

woocommerce/product-price and woocommerce/product-summary both offer full typography support, including fontSize, lineHeight, fontFamily, fontWeight, and letter spacing. You can style them directly:

```json
{
  "styles": {
    "Blocks": {
      "woocommerce/product-price": {
        "typography": {
          "fontSize": "1.25rem",
          "fontWeight": "600"
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

Product titles need a different approach depending on where they appear. For instance, inside a Product Collection grid, the title renders and offers full typography styling through the woocommerce/product-title Block. Inside the Single Product Block, the title renders through core/post-title instead:

```json
{
  "styles": {
    "Blocks": {
      "core/post-title": {
        "typography": {
          "fontSize": "var(--wp--preset--font-size--x-large)",
          "fontWeight": "700"
        }
      }
    }
  }
}
```

If you're ever unsure of the rendering, you can export the Block markup from a page. WooCommerce's template composition has changed over time, so the safest confirmation is always the markup itself.

Product descriptions follow the same pattern as prices. woocommerce/product-summary supports fontSize, lineHeight, and textAlign, along with font family, weight, style, and letter spacing. Where a template shows a longer product description rather than the short summary, look to confirm which Block is in use, since a template can reasonably use either.

### Applying color to badges, buttons, and alerts

woocommerce/product-sale-badge supports background color, gradients, and border color and radius, which makes it well suited to a pill-shaped badge:

```json
{
  "styles": {
    "Blocks": {
      "woocommerce/product-sale-badge": {
        "color": {
          "background": "var(--wp--preset--color--accent-2)"
        },
        "border": {
          "radius": "999px",
          "width": "0"
        },
        "spacing": {
          "margin": { "top": "0.5rem", "right": "0.5rem" }
        }
      }
    }
  }
}
```

The Add to Cart button (woocommerce/product-button) supports background and text color:

```json
{
  "styles": {
    "Blocks": {
      "woocommerce/product-button": {
        "color": {
          "background": "var(--wp--preset--color--contrast)",
          "text": "var(--wp--preset--color--base)"
        }
      }
    }
  }
}
```

However the Block to display cart and checkout messages, woocommerce/store-notices, supports neither color nor typography. Each notification type renders from its own dedicated template with built-in styling. To change the notification colors, you'll want to work in a child theme's stylesheet or in a template override, not in theme.json.

### Setting spacing and layout for product grids

woocommerce/product-collection, the Block behind product grids, supports alignment and its own internal layout controls. However, it doesn't support color, typography, or spacing through theme.json.

Spacing a grid consistently means two separate jobs: using the Block's built-in **Layout** panel to control columns and gaps, and styling the inner Blocks that repeat inside each grid item.

```json
{
  "styles": {
    "Blocks": {
      "woocommerce/product-image": {
        "spacing": {
          "margin": { "bottom": "0.75rem" }
        }
      },
      "woocommerce/product-title": {
        "spacing": {
          "margin": { "bottom": "0.5rem" }
        }
      }
    }
  }
}
```

The wrapper around each item in the grid (woocommerce/product-template) *does* accept color and typography, which makes it a reasonable place to set a shared text color or font across every product card without repeating the rule on each child Block.

Its layout controls are intentionally locked from further editing in the interface. This keeps the grid structure consistent, but the color and typography rules you set through theme.json still apply and cascade down to the inner Blocks unless there's a child Block override.

In summary, overall grid structure (such as column count and gaps between items) belongs to the Product Collection Block's own Layout panel. The spacing you'd expect between a product's image, title, and price within a single card belongs to the margin and padding rules on those individual inner Blocks.

## Block-level style variations for WooCommerce Blocks

Block style variations give a Block two or more selectable visual presets, switchable from the Block toolbar and stylable independently through theme.json.

woocommerce/product-button ships with two: the default of Fill, and Outline. You can restyle either directly:

```json
{
  "styles": {
    "Blocks": {
      "woocommerce/product-button": {
        "variations": {
          "outline": {
            "border": {
              "width": "2px",
              "color": "var(--wp--preset--color--contrast)"
            },
            "color": {
              "background": "transparent"
            }
          }
        }
      }
    }
  }
}
```

Product categories and tags offer a similar-looking choice between a default list and a pill-shaped display. This option belongs to core/post-terms rather than a WooCommerce Block, since WooCommerce configures that core Block with product-specific taxonomy variations rather than registering its own.

The pill style itself is registered by the active theme rather than by WordPress or WooCommerce core, so it won't appear on every install. Where it's available, style it the same way:

```json
{
  "styles": {
    "Blocks": {
      "core/post-terms": {
        "variations": {
          "pill": {
            "border": {
              "radius": "999px",
              "width": "1px"
            }
          }
        }
      }
    }
  }
}
```

> [!NOTE]
> A 'Block style variation' covered here changes a Block's visual appearance and is selectable from the Block toolbar's **Styles** panel. A 'Block variation' is used to offer different preset configurations of the same Block's attributes.

The latter is behind the switch between Categories, Tags, and Brands on the Post Terms Block. The two use cases overlap here, which is part of why it's an easy pair of concepts to mix up.

## Creating and registering custom style variations

Registering a new style variation for a Block involves registering the style itself, then styling it through theme.json. For instance, through your theme's functions.php file (or a custom plugin) you can register a variation for woocommerce/product-button with register_block_style():

```php
if ( ! function_exists( 'theme_register_button_styles' ) ) :
  function theme_register_button_styles() {
register_block_style(
'woocommerce/product-button',
array(
				'name'  => 'rounded',
				'label' => __( 'Rounded', 'your-textdomain' ),
)
);
}
endif;
add_action( 'init', 'theme_register_button_styles' );
```

> [!NOTE]
> Remember to switch out the placeholder in the array for your own text domain.

Once registered, the new **Rounded** option appears in the Block's style picker and can be styled through the styles.Blocks.woocommerce/product-button.variations path using Rounded as the key.

Global styles variations are a separate, theme-wide mechanism unrelated to individual Block styles. A theme can ship complete alternate style presets, each one a full JSON file placed in the theme's /styles directory:

```json
{
  "$schema": "https://schemas.wp.org/wp/6.7/theme.json",
  "version": 3,
  "title": "Bold",
  "settings": {
    "color": {
      "palette": [
        { "slug": "contrast", "color": "#1a1a1a", "name": "Contrast" }
      ]
    }
  }
}
```

Every file in /styles appears as a selectable preset in the Site Editor's **Styles** panel, labeled with its title property.

> [!TIP]
> You can name your file anything you like, but it's a good idea to stick to the convention given for the other files in the /styles folder.

Once you select one, WordPress copies the configuration into the database as an active customization, so a later update to the theme file won't reach stores that have already made a selection.

> [!TIP]
> When shipping a theme update, changing a /styles file after stores have adopted it updates the option for new selections only, not for stores already using it. Your theme changelogs should therefore call out style variation changes explicitly.

A style variation file can include any part of theme.json, not just color. For instance, a single file might set a full color palette, adjust the default spacing scale, and swap the heading font family together. This gives you a single, clean choice rather than several separate settings to coordinate.

## Customizing WooCommerce Blocks in the Site Editor

Every color, typography, and spacing rule described so far is also available from the Site Editor's interface, without writing theme.json directly. To do so, open **Appearance > Editor** within WordPress, then select the **Styles** icon to reach the global **Typography**, **Colors**, and **Layout** panels. Here, you'll also find a **Blocks** panel that lists every Block with registered support:

![The Blocks panel within the WordPress Site Editor.][image1]
ALT TEXT: The Blocks panel within the WordPress Site Editor.

An individual product Block will expose the same controls in its own Block settings sidebar. So, if you select a Product Price, Product Title, or Product Summary Block on a template, its **Styles** panel shows Text Color, Background Color, Typography, and Dimensions options scoped to the Block.

> [!NOTE]
> Changes made this way write to the same underlying global styles data as a theme.json edit, which is why the two approaches stay in sync.

This makes the Site Editor a way to prototype before committing anything to code. Because every change appears immediately against real product data, it's a faster way to judge whether a font size or color choice actually reads well on a product card than repeatedly editing theme.json and reloading the page.

Once a design is settled, the same values can be transcribed into theme.json for version control. Alternatively, the Site Editor's export feature can produce the finished configuration directly.

## Exporting and sharing your global styles

A finished set of global styles, whether built through theme.json or assembled in the Site Editor, can be [exported as a portable package](https://wordpress.org/documentation/article/site-editor/#how-to-export-templates-and-styles). From the **Appearance > Editor** screen in WordPress and click the page template on display. Then, select the three-dot menu next to the **Styles** icon and scroll to **Tools** > **Export**.

![The Export dialog option within the WordPress Site Editor.][image2]
ALT TEXT: The Export dialog option within the WordPress Site Editor.

WordPress generates a ZIP file containing the site's templates, template parts, and style settings. You can import this into another WooCommerce install running the same theme to reproduce the same appearance.

This is useful since it produces a snapshot of the current design that can sit alongside version control as a reference point, separate from whatever has changed in the theme's own files since. It's also the fastest way to hand a client-approved design to a second site without re-entering every color and typography choice by hand.

## Common patterns for consistent store styling

A few patterns come up repeatedly when styling a WooCommerce store: keeping every button on the site visually consistent, pairing a sale badge's color with its text, and giving prices more visual weight than the copy around them.

Consistent button styling across certain Blocks and elements is straightforward since the same Fill and Outline style names are shared:

- woocommerce/product-button
- woocommerce/mini-cart-shopping-button-Block
- woocommerce/mini-cart-cart-button-Block
- woocommerce/mini-cart-checkout-button-Block

Here, one variation definition per style name will apply the same look across all of them. Sale badge styling combines visual and text customization: color and border rules control the appearance, while the badge's text is controlled separately through a PHP filter rather than theme.json:

```php
add_filter( 'woocommerce_sale_badge_text', function( $sale_text, $product ) {
    return __( 'Limited Offer', 'your-textdomain' );
}, 10, 2 );
```

Price formatting typically means controlling the visual weight of the price rather than the number format itself, which WooCommerce handles through its currency settings. A common pattern is giving the price more visual weight than surrounding text:

```json
{
  "styles": {
    "Blocks": {
      "woocommerce/product-price": {
        "typography": {
          "fontWeight": "700",
          "fontSize": "1.1em"
        }
      }
    }
  }
}
```

## Checking your work before you ship it

Understanding which Block renders a given element, what that Block supports, and which style variations are available all depend on the WordPress and WooCommerce versions in use. In the case of Block styles (such as the Post Terms 'pill' option), the active theme matters too.

Before relying on any selector in production, look to confirm it against your own site rather than solely on an external resource:

- **Check a Block's actual supports** through the WordPress REST API's Block-types endpoint. This reflects what's registered at runtime.
- **Look at which Block is rendering** by exporting the markup for the template you're editing, particularly for product titles and taxonomy displays. WooCommerce composes these from core and WooCommerce Blocks depending on context.
- **View the available style variations** in the Site Editor's Block toolbar before writing theme.json rules against them, since some depend on the active theme rather than WooCommerce.

With those checks in place, you can move through a complete path from a bare Block theme to a fully styled WooCommerce store: typography, color, and spacing through theme.json, style variations for consistent visual choices across related Blocks, and the Site Editor for building and exporting the result.

## References

- [WooCommerce Blocks reference](https://developer.woocommerce.com/docs/block-development/reference/block-references/).
- [Theming WooCommerce Blocks](https://developer.woocommerce.com/docs/theming/block-theme-development/theming-woo-blocks/).
- [On-Sale Badge Block](https://developer.woocommerce.com/docs/block-development/extensible-blocks/product-sale-badge-block/).
- WordPress' [Global settings and styles](https://developer.wordpress.org/themes/global-settings-and-styles/).
- WordPress' [Global settings and styles: Styles](https://developer.wordpress.org/themes/global-settings-and-styles/styles/).
- [Customizing the Cart and Checkout Pages](https://woocommerce.com/document/woocommerce-store-editing/customizing-cart-and-checkout/).

## Next steps

This guide covers the styling foundation for WooCommerce Block themes. The rest of this section builds on it with templates, page-level customizations, and CSS:

1. [Template hierarchy reference](/docs/theming/block-theme-development/template-hierarchy/). How to work with and override WooCommerce Block templates.
2. Product page theming. Customizing the single product template.
3. Shop and archive page theming. Working with product listing template customizations.
4. [Cart and Checkout theming](https://developer.woocommerce.com/docs/theming/block-theme-development/cart-and-checkout/). How to style the Cart and Checkout Blocks, including buttons and the mobile submit container.
5. [CSS styling](https://developer.woocommerce.com/docs/theming/block-theme-development/css-styling/). CSS approaches and class name conventions for what theme.json doesn't cover.
6. [Theming WooCommerce Blocks](https://developer.woocommerce.com/docs/theming/block-theme-development/theming-woo-blocks/). An overview of core concepts, Block templates, and template structure.

[image1]: _media/global-styles-blocks-panel.png
[image2]: _media/global-styles-export-menu.png

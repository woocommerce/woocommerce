# Translation management

As mentioned in [Translation basics](../../internal-developers/translations/translation-basics.md), all translations are managed using [GlotPress](https://wordpress.org/plugins/glotpress/). The translations of the WooCommerce Blocks plugin can be found on <https://translate.wordpress.org/projects/wp-plugins/woo-gutenberg-products-block/>.

## Roles

-   [Translation Contributors](#translation-contributors)
-   [Project Translation Editor (PTE)](#project-translation-editor-pte)
-   [General Translation Editor (GTE)](#general-translation-editor-gte)

### Translation Contributors

A Translation Contributors can suggest translations. These suggested translations need to be verified by a General Translation Editor (GTE) or a Project Translation Editor (PTE).

See also <https://make.wordpress.org/polyglots/handbook/about/roles-and-capabilities/#translation-contributor>.

### Project Translation Editor (PTE)

A Project Translation Editor can:

-   approve translations that are suggested by a Translation Contributor
-   change existing translations
-   add new translations

PTE permissions need to be requested via <https://make.wordpress.org/polyglots/>. If you're a developer of the WooCommerce Blocks plugin, you can request PTE permissions using the following template:

```text
PTE Request for WooCommerce Blocks

I am the plugin co-author for WooCommerce Blocks, and I’d like to be able to approve translation for our plugin. Please add my WordPress.org user account as translation editor for their respective locales:

Name: WooCommerce Blocks
URL: https://wordpress.org/plugins/woo-gutenberg-products-block/

o #ar – @username
o #bn_BD – @username, @username
o #da_DK – @username
o #de_CH – @username
o etc...

If you have any questions, just comment here. Thank you!

#editor-requests
```

See also <https://make.wordpress.org/polyglots/handbook/about/roles-and-capabilities/#project-translation-editor>.

#### Formal vs. informal translations

Dutch and German have both formal and informal translations:

-   `#de_DE` and `#de_DE_formal`
-   `#de_CH` and `#de_CH_formal`
-   `#nl_NL` and `#nl_NL_formal`

It is sufficient to request the PTE permissions for the informal translations only. The Polyglots team will automatically assign the PTE permissions for both the formal and informal translations.

## General Translation Editor (GTE)

While a PTE can approve, change and add translations for a certain plugin or theme, a GTE can approve, change and add translations to all plugins and themes of that corresponding locale. In addition, a GTE can approve, change and add WordPress core translations.

While it's possible to request GTE permissions, usually a Locale Manager of the corresponding locale promotes a GTE.

See also <https://make.wordpress.org/polyglots/handbook/about/roles-and-capabilities/#project-translation-editor>.

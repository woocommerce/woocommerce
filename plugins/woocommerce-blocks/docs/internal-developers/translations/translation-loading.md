# Translation files and loading

## Translation files

### POT (Portable Object Template) file

POT stands for `Portable Object Template` and contains all original strings, in English. It can be created manually using [WP-CLI](https://wp-cli.org/) or [Poedit](https://poedit.net/). As the WooCommerce Blocks plugin is hosted on WordPress.org, we don't need to manually create the POT file. GlotPress automatically generates that file in the background, once we release a new version of the plugin.

The POT file is human-readable and named `woo-gutenberg-products-block.pot`. It will not be downloaded to the site that is using the WooCommerce Blocks plugin. If we would generate the POT file manually, it would look like this:

```pot
"Project-Id-Version: WooCommerce Blocks\n"
"POT-Creation-Date: 2022-05-25 19:01+0700\n"
"PO-Revision-Date: 2022-05-25 19:00+0700\n"
"Last-Translator: \n"
"Language-Team: \n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\n"
"X-Generator: Poedit 3.0.1\n"
"X-Poedit-Basepath: ..\n"
"X-Poedit-Flags-xgettext: --add-comments=translators:\n"
"X-Poedit-WPHeader: woocommerce-gutenberg-products-block.php\n"
"X-Poedit-SourceCharset: UTF-8\n"
"X-Poedit-KeywordsList: __;_e;_n:1,2;_x:1,2c;_ex:1,2c;_nx:4c,1,2;esc_attr__;"
"esc_attr_e;esc_attr_x:1,2c;esc_html__;esc_html_e;esc_html_x:1,2c;_n_noop:1,2;"
"_nx_noop:3c,1,2;__ngettext_noop:1,2\n"
"X-Poedit-SearchPath-0: .\n"
"X-Poedit-SearchPathExcluded-0: *.min.js\n"
"X-Poedit-SearchPathExcluded-1: vendor\n"
"X-Poedit-SearchPathExcluded-2: node_modules\n"

#: assets/js/blocks/handpicked-products/block.js:42
#: assets/js/blocks/product-best-sellers/block.js:34
#: assets/js/blocks/product-category/block.js:157
#: assets/js/blocks/product-new/block.tsx:36
#: assets/js/blocks/product-on-sale/block.js:52
#: assets/js/blocks/product-tag/block.js:121
#: assets/js/blocks/product-top-rated/block.js:36
#: assets/js/blocks/products-by-attribute/block.js:46
#: assets/js/blocks/single-product/edit/layout-editor.js:56
msgid "Layout"
msgstr ""

[...]
```

See also <https://developer.wordpress.org/plugins/internationalization/localization/#pot-portable-object-template-files>.

### PO (Portable Object) file

PO stands for `Portable Object`, contains both the original and the translated strings and is based on the POT file. Similar to the POT file, it can also be created manually using [WP-CLI](https://wp-cli.org/) or [Poedit](https://poedit.net/). As mentioned before, this step is not necessary for the WooCommerce Blocks plugin, as it's hosted on WordPress.org. The PO file will be generated within 30 minutes after a new translation had been added via <https://translate.wordpress.org/projects/wp-plugins/woo-gutenberg-products-block/>.

The PO file is human-readable and named `woo-gutenberg-products-block-{LANGUAGE-CODE}.po`, e.g. `woo-gutenberg-products-block-de_DE.po`. It can be found in the `/wp-content/languages/plugin` folder. The PO file looks like this:

```po
# Translation of Plugins - WooCommerce Blocks - Stable (latest release) in German
# This file is distributed under the same license as the Plugins - WooCommerce Blocks - Stable (latest release) package.
msgid ""
msgstr ""
"PO-Revision-Date: 2022-05-22 10:58:25+0000\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=2; plural=n != 1;\n"
"X-Generator: GlotPress/4.0.0-alpha.1\n"
"Language: de\n"
"Project-Id-Version: Plugins - WooCommerce Blocks - Stable (latest release)\n"

#: assets/js/blocks/featured-product/block.json
#: build/featured-product/block.json
msgctxt "block description"
msgid "Highlight a product or variation."
msgstr "Ein Produkt oder eine Variante visuell hervorheben und zum sofortigen Handeln auffordern."

#: assets/js/blocks/featured-product/block.json
#: build/featured-product/block.json
msgctxt "block title"
msgid "Featured Product"
msgstr "Hervorgehobenes Produkt"

[...]
```

See also <https://developer.wordpress.org/plugins/internationalization/localization/#po-portable-object-files>.

### MO (Machine Object) file

MO stands for `Machine Object`, contains both the original and the translated strings and is based on the PO file. As the POT and the PO files, the MO file can be created manually, but this is not necessary for the WooCommerce Blocks plugin.

The MO file is only machine-readable and named `woo-gutenberg-products-block-{LANGUAGE-CODE}.mo`, e.g. `woo-gutenberg-products-block-de_DE.mo` It can be found in the `/wp-content/languages/plugin` folder. As the MO file is machine-readable only, it cannot be viewed and the MO file only handles translations within PHP files.

See also <https://developer.wordpress.org/plugins/internationalization/localization/#mo-machine-object-files>.

### JSON files

As mentioned before, the MO file only handle translations within PHP files. Translations within JavaScript and TypeScript files are handled by JSON files. These JSON files contain both the original and the translated strings and are also based on the PO file. Similar to the MO file, the JSON files can be manually created, which is not needed in this case.

The JSON files are human-readable and named `woo-gutenberg-products-block-{LANGUAGE-CODE}-{md5(FILE-PATH)}.json`, e.g. `woo-gutenberg-products-block-de_DE-0a066f0c536e17452f6345c5d072335b.json`. They can be found in the `/wp-content/languages/plugin` folder. The JSON files look like this:

```json
{
	"translation-revision-date": "2022-05-22 10:58:25+0000",
	"generator": "GlotPress/4.0.0-alpha.1",
	"domain": "messages",
	"locale_data": {
		"messages": {
			"": {
				"domain": "messages",
				"plural-forms": "nplurals=2; plural=n != 1;",
				"lang": "de"
			},
			"Price between %1$s and %2$s": [ "Preis zwischen %1$s und %2$s" ],
			"Discounted price:": [ "Reduzierter Preis:" ],
			"Previous price:": [ "Vorheriger Preis:" ]
		}
	},
	"comment": { "reference": "build/product-price-frontend.js" }
}
```

## Loading translations

Loading translations for PHP files works differently to loading translations for JS/TS files.

### Loading translations for PHP files

As mentioned in [Translation Basics](translation-basics.md), loading the translations for PHP files does not regquire any extra code, as long as the plugin is hosted on WordPress.org and does not support WordPress versions prior to 4.6.

### Loading translations for TJS/TS files

To load JS/TS translations, you need to execute the `wp_set_script_translations()` function. Currently, this function is part of the following code block in `/src/Assets/Api.php`:

```php
/**
 * Registers a script according to `wp_register_script`, adding the correct prefix, and additionally loading translations.
 *
 * When creating script assets, the following rules should be followed:
 *   1. All asset handles should have a `wc-` prefix.
 *   2. If the asset handle is for a Block (in editor context) use the `-block` suffix.
 *   3. If the asset handle is for a Block (in frontend context) use the `-block-frontend` suffix.
 *   4. If the asset is for any other script being consumed or enqueued by the blocks plugin, use the `wc-blocks-` prefix.
 *
 * @since 2.5.0
 * @throws Exception If the registered script has a dependency on itself.
 *
 * @param string $handle        Unique name of the script.
 * @param string $relative_src  Relative url for the script to the path from plugin root.
 * @param array  $dependencies  Optional. An array of registered script handles this script depends on. Default empty array.
 * @param bool   $has_i18n      Optional. Whether to add a script translation call to this file. Default: true.
 */
public function register_script( $handle, $relative_src, $dependencies = [], $has_i18n = true ) {
  $script_data = $this->get_script_data( $relative_src, $dependencies );

  if ( in_array( $handle, $script_data['dependencies'], true ) ) {
    if ( $this->package->feature()->is_development_environment() ) {
      $dependencies = array_diff( $script_data['dependencies'], [ $handle ] );
        add_action(
          'admin_notices',
          function() use ( $handle ) {
              echo '<div class="error"><p>';
              /* translators: %s file handle name. */
              printf( esc_html__( 'Script with handle %s had a dependency on itself which has been removed. This is an indicator that your JS code has a circular dependency that can cause bugs.', 'woo-gutenberg-products-block' ), esc_html( $handle ) );
              echo '</p></div>';
          }
        );
    } else {
      throw new Exception( sprintf( 'Script with handle %s had a dependency on itself. This is an indicator that your JS code has a circular dependency that can cause bugs.', $handle ) );
    }
  }

  /**
   * Filters the list of script dependencies.
   *
   * @param array $dependencies The list of script dependencies.
   * @param string $handle The script's handle.
   * @return array
   */
  $script_dependencies = apply_filters( 'woocommerce_blocks_register_script_dependencies', $script_data['dependencies'], $handle );

  wp_register_script( $handle, $script_data['src'], $script_dependencies, $script_data['version'], true );

  if ( $has_i18n && function_exists( 'wp_set_script_translations' ) ) {
    wp_set_script_translations( $handle, 'woo-gutenberg-products-block', $this->package->get_path( 'languages' ) );
  }
}
```

## Loading fallback translations

By default, the WooCommerce Blocks plugin tries to load the translation from <https://translate.wordpress.org/projects/wp-plugins/woo-gutenberg-products-block/>. If a translation cannot be loaded, the plugin tries to load the corresponding translation from <https://translate.wordpress.org/projects/wp-plugins/woocommerce/>.

The code that loads the fallback translation, is located in `woocommerce-gutenberg-products-block.php`.

## Loading fallback translations for PHP files

The following function handles the loading of fallback translations for PHP files:

```php
/**
 * Filter translations so we can retrieve translations from Core when the original and the translated
 * texts are the same (which happens when translations are missing).
 *
 * @param string $translation Translated text based on WC Blocks translations.
 * @param string $text        Text to translate.
 * @param string $domain      The text domain.
 * @return string WC Blocks translation. In case it's the same as $text, Core translation.
 */
function woocommerce_blocks_get_php_translation_from_core( $translation, $text, $domain ) {
	if ( 'woo-gutenberg-products-block' !== $domain ) {
		return $translation;
	}

	// When translation is the same, that could mean the string is not translated.
	// In that case, load it from core.
	if ( $translation === $text ) {
		return translate( $text, 'woocommerce' ); // phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction, WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.TextDomainMismatch
	}
	return $translation;
}

add_filter( 'gettext', 'woocommerce_blocks_get_php_translation_from_core', 10, 3 );
```

## Loading fallback translations for JS/TS files

The following function handles the loading of fallback translations for JS/TS files:

```php
/**
 * WordPress will look for translation in the following order:
 * - wp-content/plugins/woocommerce-blocks/languages/woo-gutenberg-products-block-{locale}-{handle}.json
 * - wp-content/plugins/woocommerce-blocks/languages/woo-gutenberg-products-block-{locale}-{md5-handle}.json
 * - wp-content/languages/plugins/woo-gutenberg-products-block-{locale}-{md5-handle}.json
 *
 * We check if the last one exists, and if it doesn't we try to load the
 * corresponding JSON file from the WC Core.
 *
 * @param string|false $file   Path to the translation file to load. False if there isn't one.
 * @param string       $handle Name of the script to register a translation domain to.
 * @param string       $domain The text domain.
 *
 * @return string|false        Path to the translation file to load. False if there isn't one.
 */
function load_woocommerce_core_js_translation( $file, $handle, $domain ) {
	if ( 'woo-gutenberg-products-block' !== $domain ) {
		return $file;
	}

	$lang_dir = WP_LANG_DIR . '/plugins';

	/**
	 * We only care about the translation file of the feature plugin in the
	 * wp-content/languages folder.
	 */
	if ( false === strpos( $file, $lang_dir ) ) {
		return $file;
	}

	// If the translation file for feature plugin exist, use it.
	if ( is_readable( $file ) ) {
		return $file;
	}

	global $wp_scripts;

	if ( ! isset( $wp_scripts->registered[ $handle ], $wp_scripts->registered[ $handle ]->src ) ) {
		return $file;
	}

	$handle_src      = explode( '/build/', $wp_scripts->registered[ $handle ]->src );
	$handle_filename = $handle_src[1];
	$locale          = determine_locale();
	$lang_dir        = WP_LANG_DIR . '/plugins';

	// Translations are always based on the unminified filename.
	if ( substr( $handle_filename, -7 ) === '.min.js' ) {
		$handle_filename = substr( $handle_filename, 0, -7 ) . '.js';
	}

	$core_path_md5 = md5( 'packages/woocommerce-blocks/build/' . $handle_filename );

	/**
	 * Return file path of the corresponding translation file in the WC Core is
	 * enough because `load_script_translations()` will check for its existence
	 * before loading it.
	 */
	return $lang_dir . '/woocommerce-' . $locale . '-' . $core_path_md5 . '.json';
}

add_filter( 'load_script_translation_file', 'load_woocommerce_core_js_translation', 10, 3 );
```

---
post_title: String localization
sidebar_label: String localization
---

# String localization

WooCommerce is translation-ready out of the box. This page covers how to write localizable strings, contribute translations, create custom translations, and update locale-specific data such as countries and subdivisions.

## Translating WooCommerce

WooCommerce is already translated into several languages and only needs a translation file for your language. In most cases, you can contribute translations on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/woocommerce/).

To create custom translations, you can also use a tool such as [Poedit](https://poedit.net/).

### Set up WordPress in your language

To set your WordPress site's language:

1. Go to `WP Admin > Settings > General` and adjust the `Site Language`.
2. Go to `WP Admin > Dashboard > Updates` and click the `Update Translations` button.

Once this has been done, the shop displays in your locale if the language file exists. Otherwise, you need to create the language files.

### Writing localizable strings

1. Use the `woocommerce` textdomain in all strings.
2. When using dynamic strings in `printf()` or `sprintf()`, use numbered arguments if you are replacing more than one string. For example, `Test %s string %s.` should be `Test %1$s string %2$s.`
3. Use sentence case. For example, `Some Thing` should be `Some thing`.
4. Avoid HTML. If needed, insert the HTML using `sprintf()`.

For more information, see the WordPress core documentation for [i18n for WordPress Developers](https://codex.wordpress.org/I18n_for_WordPress_Developers).

### Contribute translations to core

We encourage contributions to our translations. If you want to add translated strings or start a new translation, register at WordPress.org and submit your translations to [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/woocommerce/) for approval.

Both stable and development versions of WooCommerce are available for translation. When you install or update WooCommerce, WordPress will automatically fetch a 100% complete translation for your language. If such a translation isn't available, you can download it manually or contribute to complete the translation, benefiting all users.

If you're new to translating, check out the [Translators Handbook](https://make.wordpress.org/polyglots/handbook/tools/glotpress-translate-wordpress-org/) to get started.

### Download translations manually

1. Go to [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/woocommerce) and look for your language in the list.
2. Click the title to be taken to the section for that language.

    ![Screenshot of WooCommerce translation page on WordPress.org](https://developer.woocommerce.com/wp-content/uploads/2023/12/2016-02-17-at-09.57.png)

3. Click the heading under `Set/Sub Project` to view and download a Stable version.

    ![List of versions available for selected language](https://developer.woocommerce.com/wp-content/uploads/2023/12/2016-02-17-at-09.59.png)

4. Scroll to the bottom for export options. Export a `.mo` file for use on your site.
5. Rename this file to `woocommerce-YOURLANG.mo`. For example, Great Britain English should be `woocommerce-en_GB.mo`. The corresponding language code can be found by opening the desired language on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/woocommerce/). The language code is visible in the upper-right corner.

    ![Screenshot of plugin card with associated language code](https://developer.woocommerce.com/wp-content/uploads/2023/12/Screenshot-2023-10-17-at-09.44.53.png)

6. Upload the file to your site under `wp-content/languages/woocommerce/`. Once uploaded, this translation file may be used.

## Creating custom translations

WooCommerce includes a language file (`.pot` file) that contains all of the English text. You can find this language file inside the plugin folder in `woocommerce/i18n/languages/`.

### Create custom translations with Poedit

WooCommerce comes with a `.pot` file that can be imported into Poedit to translate.

To get started:

1. Open Poedit and select `Create new translation from POT template`.
2. Choose `woocommerce.pot` and Poedit will show the catalog properties window.

    ![Poedit catalog properties window](https://developer.woocommerce.com/wp-content/uploads/2023/12/Screen-Shot-2013-05-09-at-10.16.46.png)

3. Enter your name and details, so other translators know who you are, and click `OK`.
4. Save your `.po` file. Name it based on what you are translating to, such as `woocommerce-en_GB.po` for a Great Britain English translation. Now the strings are listed.

    ![Poedit strings list](https://developer.woocommerce.com/wp-content/uploads/2023/12/Screen-Shot-2013-05-09-at-10.20.58.png)

5. Save after translating strings. The `.mo` file is generated automatically.
6. Update your `.po` file by opening it and then going to `Catalog > Update from POT file`.
7. Choose the file and it will be updated accordingly.

### Make custom translations upgrade safe

WooCommerce keeps translations in `wp-content/languages/plugins`, like all other plugins. If you wish to include a custom translation, you can use the directory `wp-content/languages/woocommerce`, or you can use a snippet to load a custom translation stored elsewhere:

```php
// Code to be placed in functions.php of your theme or a custom plugin file.
add_filter( 'load_textdomain_mofile', 'load_custom_plugin_translation_file', 10, 2 );

/**
 * Replace 'textdomain' with your plugin's textdomain. e.g. 'woocommerce'.
 * File to be named, for example, yourtranslationfile-en_GB.mo
 * File to be placed, for example, wp-content/languages/textdomain/yourtranslationfile-en_GB.mo
 */
function load_custom_plugin_translation_file( $mofile, $domain ) {
    if ( 'textdomain' === $domain ) {
        $mofile = WP_LANG_DIR . '/textdomain/yourtranslationfile-' . get_locale() . '.mo';
    }

    return $mofile;
}
```

## Translation tools

Some third-party tools can help with translations:

- [Loco Translate](https://wordpress.org/plugins/loco-translate/) provides in-browser editing of WordPress translation files and integration with automatic translation services.
- [Say what?](https://wordpress.org/plugins/say-what/) allows you to translate or modify specific words without editing a WordPress theme's `.po` file.
- [String Locator](https://wordpress.org/plugins/string-locator/) enables quick searches within themes, plugins, or WordPress core, displaying a list of files with the matching text and its line number.

## FAQ

### Why are some Checkout page strings not being translated?

You may see that some Checkout page strings are not translated. For example, the `Local pickup` shipping method, `Cash on delivery` payment method, or a Privacy Policy message may remain in English while the rest of the form is translated:

![Checkout page with some strings not translated](https://developer.woocommerce.com/wp-content/uploads/2023/12/not_translated.jpg)

This usually happens when you first install WooCommerce with the default site language, English, and later change the site language to another one. In WooCommerce, the strings that have not been translated in the screenshot are stored in the database after the initial WooCommerce installation. Therefore, if the site language is changed to another one, there is no way for WooCommerce to detect a translatable string since these are database entries.

To fix it, navigate to the WooCommerce settings corresponding to the string you need to change and update the translation there directly.

For `Local pickup`:

1. Go to `WooCommerce > Settings > Shipping > Shipping Zones`.
2. Select the shipping zone where "Local pickup" is listed.
3. Open "Local pickup" settings.
4. Rename the method using your translation.
5. Save the setting.

For `Cash on delivery`:

1. Go to `WooCommerce > Settings > Payments`.
2. Select the "Cash on delivery" payment method.
3. Open its settings.
4. Rename the method title, description, and instructions using your translation.
5. Save the setting.

For the privacy policy message:

1. Go to `WooCommerce > Settings > Accounts & Privacy`.
2. Scroll to the "Privacy policy" section.
3. Edit both the `Registration privacy policy` and `Checkout privacy policy` fields with your translation.
4. Save the settings.

Navigate back to the Checkout page. Translations should be reflected there.

### Why do some translated strings not show up on the front end?

If some of your translated strings don't show up as expected on your WooCommerce site, first check if these strings have both a single and plural form in the Source text section. To do so, open the corresponding translation on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/woocommerce/), such as [the translation for Product and Products](https://translate.wordpress.org/projects/wp-plugins/woocommerce/stable/de/default/?filters%5Bstatus%5D=either&filters%5Boriginal_id%5D=577764&filters%5Btranslation_id%5D=24210880).

This screenshot shows that the singular translation is available:

![Screenshot showing that the singular translation is available](https://developer.woocommerce.com/wp-content/uploads/2023/12/Screenshot-2023-10-17-at-10.10.06.png)

This screenshot shows that the plural translation is not available:

![Screenshot showing that the plural translation is not available](https://developer.woocommerce.com/wp-content/uploads/2023/12/Screenshot-2023-10-17-at-10.10.21.png)

## Countries and subdivisions

WooCommerce comes complete with a comprehensive list of countries and subdivisions, such as provinces or states, that are used in various parts of the user interface.

Countries and their subdivisions periodically change. In these cases, you can file a [bug report](https://github.com/woocommerce/woocommerce/issues/new?template=1-bug-report.yml) or [submit a pull request](/docs/contribution/contributing). However, our policy is only to accept changes if they align with the current version of the [CLDR project](https://cldr.unicode.org/). It is generally best to review CLDR and, if necessary, propose a change there first before asking that it be adopted by WooCommerce.

This approach may not be suitable in all cases, because it can take time for CLDR to accept updates. In such cases, you can still modify the lists of countries and subdivisions by using custom snippets like the following:

- [Snippet to add a country](/docs/code-snippets/add-a-country)
- [Snippet to add or modify states](/docs/code-snippets/add-or-modify-states)

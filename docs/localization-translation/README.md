# Translating WooCommerce

WooCommerce is already translated into several languages and is translation-ready right out of the box. All that’s needed is a translation file for your language.

There are several methods to create a translation, most of which are outlined in the WordPress Codex. In most cases you can contribute to the project on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/woocommerce/).

To create custom translations:

-   For a simple approach, choose the [Loco Translate](https://wordpress.org/plugins/loco-translate/) plugin.
-   If you're more experienced, consider [Poedit ](https://poedit.net/).

This document provides details on both methods.

## Set up WordPress in your language

To set your WordPress site's language:

1. Go to `WP Admin » Settings » General` and adjust the `Site Language`.
2. Go to `WP Admin » Dashboard » Updates` and click the `Update Translations` button.

Once this has been done, the shop displays in your locale if the language file exists. Otherwise, you need to create the language files (process explained below).

## Contributing your localization to core

We encourage contributions to our translations. If you want to add translated strings or start a new translation, simply register at WordPress.org and submit your translations to [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/woocommerce/) for approval.

## Translating WooCommerce into your language

Both stable and development versions of WooCommerce are available for translation. When you install or update WooCommerce, WordPress will automatically fetch a 100% complete translation for your language. If such a translation isn't available, you can either download it manually or contribute to complete the translation, benefiting all users.

If you’re new to translating, check out the [translators handbook](https://make.wordpress.org/polyglots/handbook/tools/glotpress-translate-wordpress-org/) to get started.

### Downloading translations from translate.wordpress.org manually

1. Go to [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/woocommerce) and look for your language in the list.
2. Click the title to be taken to the section for that language.

    ![screenshot](https://woocommerce.com/wp-content/uploads/2012/01/2016-02-17-at-09.57.png)

3. Click the heading under `Set/Sub Project` to view and download a Stable version.

    ![screenshot](https://woocommerce.com/wp-content/uploads/2012/01/2016-02-17-at-09.59.png)

4. Scroll to the bottom for export options. Export a `.mo` file for use on your site.

    ![screenshot](https://woocommerce.com/wp-content/uploads/2012/01/2016-02-17-at-10.00.png)

5. Rename this file to `woocommerce-YOURLANG.mo` (e.g., Great Britain English should be `en_GB`).
6. Upload to your site under `wp-content/languages/woocommerce/`. Once uploaded, this translation file may be used.

## Creating custom translations

WooCommerce includes a language file (`.pot` file) that contains all of the English text. You can find this language file inside the plugin folder in `woocommerce/i18n/languages/`.

### Creating custom translations with Loco Translate

1. Install and activate the [Loco Translate](https://wordpress.org/plugins/loco-translate/) plugin.
2. Go to the new Loco Translate menu item created in your Dashboard with five sub-sections.
3. Go to the `Plugins` section, and select `WooCommerce`.

    ![screenshot](https://woocommerce.com/wp-content/uploads/2012/01/loco-translate-woocommerce.png)

4. Add new languages using the `Add New Language` link.

    ![screenshot](https://woocommerce.com/wp-content/uploads/2012/01/loco-translate-new-language.png)

5. Choose the language to add in the list, or enter the language ISO code (ex: `fr_FR`, `en_US` etc.), select the folder to add the translation files to, and click `Start Translating`.

    ![screenshot](https://woocommerce.com/wp-content/uploads/2012/01/loco-translate-create-language.png)

    > **Note:**
    >
    > - Loco Translate offers three different locations for where to create the translation. The best choice for your own translation is Custom, not just to prevent custom translations from being undone by updates, but also because it’s needed for strings in extensions like WooCommerce Subscriptions to be properly translated. Avoid the Author location since it’s inside the plugin and will be overwritten by extension updates, as well as the System location, which could be overwritten by translations from translate.wordpress.org.
    > - If you encounter any technical issues while using Loco Translate, please get in touch with [the Loco Translate support team](https://wordpress.org/support/plugin/loco-translate/).

6. Save when finished.

    ![screenshot](https://woocommerce.com/wp-content/uploads/2012/01/loco-translate-translating-1.png)

    > **Note:** When a new version of WooCommerce is released and updated on your site, you need refresh the .po file to look for new strings. Use the “Sync” button to find any new string to start translating.

## Creating custom translations with PoEdit

[Poedit ](https://poedit.net/) is a more advanced alternative to the [Loco Translate](https://wordpress.org/plugins/loco-translate/) plugin. Only advanced users or translators wishing to create a custom translation to WooCommerce should attempt this method.

WooCommerce comes with a `.pot` file that can be imported into PoEdit to translate.

To get started:

1. Open PoEdit and select `Create new translation from POT template`.
2. Choose `woocommerce.pot` and PoEdit will show the catalog properties window.

    ![screenshot](https://woocommerce.com/wp-content/uploads/2012/01/Screen-Shot-2013-05-09-at-10.16.46.png)

3. Enter your name and details, so other translators know who you are, and click `OK`.
4. Save your `.po` file. Name it based on what you are translating to, i.e., a GB translation is saved as `woocommerce-en_GB.po`. Now the strings are listed.

    ![screenshot](https://woocommerce.com/wp-content/uploads/2012/01/Screen-Shot-2013-05-09-at-10.20.58.png)

5. Save after translating strings. The `.mo` file is generated automatically.
6. Update your `.po` file by opening it and then go to `Catalog » Update from POT file`.
7. Choose the file and it will be updated accordingly.

## Making your translation upgrade safe

> **Note:** We are unable to provide support for customizations under our [Support Policy](http://www.woocommerce.com/support-policy/). If you need to further customize a snippet, or extend its functionality, we highly recommend [Codeable](https://codeable.io/?ref=z4Hnp), or a [Certified WooExpert](https://woocommerce.com/experts/).

WooCommerce keeps translations in `wp-content/languages/plugins`, like all other plugins. But if you wish to include a custom translation, you can use the directory `wp-content/languages/woocommerce`, or you can use a snippet to load a custom translation stored elsewhere:

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

## Translating text without a localization file

With the [Say what?](https://wordpress.org/plugins/say-what/) plugin, you can effortlessly translate or modify specific words without delving into a WordPress theme's `.po` file. No custom coding needed.

Upon activation, the plugin prompts you to specify:

1. **Original String**: The text you aim to translate. Check the plugin source code for the precise string.
2. **Text Domain**: Use `woocommerce`.
3. **Replacement Text**: The text you'd like to display in place of the original.

## FAQ

### Why some strings on the Checkout page are not being translated?

You may see that some of the strings are not being translated on the Checkout page. For example, in the screenshot below, `Local pickup` shipping method, `Cash on delivery` payment method and a message related to Privacy Policy are not being translated to Russian while the rest of the form is indeed translated:

![screenshot](https://woocommerce.com/wp-content/uploads/2012/01/not_translated.jpg)

This usually happens when you first install WooCommerce and select default site language (English) and later change the site language to another one. In WooCommerce, the strings that have not been translated in the screenshot, are stored in the database after the initial WooCommerce installation. Therefore, if the site language is changed to another one, there is no way for WooCommerce to detect a translatable string since these are database entries.

In order to fix it, navigate to WooCommerce settings corresponding to the string you need to change and update the translation there directly. For example, to fix the strings in our case above, you would need to do the following:

**Local pickup**:

1.  Go to `WooCommerce » Settings » Shipping » Shipping Zones`.
2.  Select the shipping zone where "Local pickup" is listed.
3.  Open "Local pickup" settings.
4.  Rename the method using your translation.
5.  Save the setting.

**Cash on delivery**:

1.  Go to `WooCommerce » Settings » Payments`.
2.  Select the "Cash on delivery" payment method.
3.  Open its settings.
4.  Rename the method title, description, and instructions using your translation.
5.  Save the setting.

**Privacy policy message**:

1.  Go to `WooCommerce » Settings » Accounts & Privacy`.
2.  Scroll to the "Privacy policy" section.
3.  Edit both the `Registration privacy policy` and `Checkout privacy policy` fields with your translation.
4.  Save the settings.

Navigate back to the Checkout page – translations should be reflected there.

### I have translated the strings I needed, but some of them don’t show up translated on the front end. Why?

If some of your translated strings don’t show up as expected on your WooCommerce site, the first thing to check is if these strings have both a Single and Plural form in the Source text section.

If this is the case, then it is necessary to translate both these forms for your translation to work as expected. To do this, please use the Single and Plural tabs that show up in the translation section:

![screenshot](https://woocommerce.com/wp-content/uploads/2023/03/Single_Plural_Loco_Translate-1.png)

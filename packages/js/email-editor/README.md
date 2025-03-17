# WooCommerce Email Editor

This folder contains the code for the WooCommerce Email Editor JS Package.
We aim to extract the package as an independent library, so it can be used in other projects.

You can try the email editor in [the WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/woocommerce/woocommerce/refs/heads/trunk/packages/js/email-editor/blueprint.json).

You can locate the PHP package here `packages/php/email-editor`

## Workflow Commands

We use `pnpm` run scripts to run the commands. You can run them using `pnpm run <command>`.
Please start with `pnpm install`.

```bash
pnpm run build                              # runs the build command
pnpm run start                              # starts the webpack development server
pnpm run lint:css                           # runs stylelint on all .scss files
pnpm run lint:css-fix                       # fixes errors reported by `pnpm run lint:css`
pnpm run lint:js                            # runs eslint on all js and ts files (including the .jsx and .tsx versions)
pnpm run lint:js-fix                        # fixes errors reported by `pnpm run lint:js`
pnpm run check-types                        # runs type check on all typescript files
pnpm run format                             # runs prettier on files. This uses WordPress coding standards.
```

## Development

### Main parts

**Email Editor** – JS application. Custom editor built with WordPress (`@wordpress`) JS packages. Most important ones are `@wordpress/block-editor`, `@wordpress/editor`, `@wordpress/data` and `@wordpress/components`. There is also some PHP code to bootstrap the editor.
**Storage** – we store email content as custom post-type + we use templates (a couple of dummy hardcoded templates) to carry shared parts of the content (header, footer) and style settings. Currently, we store complete style settings per template.
**Renderer** – responsible for converting saved HTML from Gutenberg editor to HTML for email clients.
**Theme Controller** – The theme controller is used to generate settings and styles for the editor. We can define which features for working with content are available in settings. The styles are also used in the Render.

### Dependencies

#### Rich-text

The **Personalization tags** feature relies on the `@wordpress/rich-text` package, which is included in both the Gutenberg plugin and WordPress core.
To ensure the correct functionality of the Email Editor and its features, you must use **at least version 7.14.0** of the `@wordpress/rich-text` package.
The required minimum version of this package is stored in the assets directory.
If your WordPress installation does not use the Gutenberg plugin or does not include the required version, replace the existing `@wordpress/rich-text` package with the one provided in the assets directory.

### Email Editor

-   Bootstrapped in the plugin in the [email editor controller](https://github.com/mailpoet/mailpoet/blob/13bf305aeb29bbadd0695ee02a3735e62cc4f21f/mailpoet/lib/EmailEditor/Integrations/MailPoet/EmailEditor.php)
-   **Components folder** - basically the whole UI of the editor. Most of Gutenberg's blocks magic happens in block-editor folder.
-   **Hooks folder** – several custom hooks mostly to help us pass around data and process data from the store or combine them from multiple stores.
-   **Store folder** – classic `wordpress/data` store. We try to use stores from packages we build on as much as possible, but sometimes we need to add an action etc.
-   **Layouts folder** – contains one layout. Gutenberg support flex, grid but these can't be used because email clients don't support them. Flex-email is restricted layout that supports some features from flex. It is used for buttons.
-   **Blocks folder** – when we add support for block, we usually need to do some adjustments and hide some styling options. This is done via Blocks API and save in this folder.
-   **Lock-unlock** – key to open the Pandora box with private components from WP packages
-   **.sccs files** – we don't write much CSS. We load styles for the post editor (this is done in page controller) but sometimes we need some adjustments of have a custom ui
-   **Custom blocks** - MailPoet custom blocks should be built in [mailpoet-custom-email-editor-blocks folder](https://github.com/mailpoet/mailpoet/tree/13bf305aeb29bbadd0695ee02a3735e62cc4f21f/mailpoet/assets/js/src/mailpoet-custom-email-editor-blocks)

## Email clients

### WEB CLIENTS

| Client                  | Platform | Versions to Support                            | Rendering Engine | Percentage (Litmus) | Litmus Check | Note                                                                            |
| ----------------------- | -------- | ---------------------------------------------- | ---------------- | ------------------- | ------------ | ------------------------------------------------------------------------------- |
| Gmail.com               | Browser  | Latest: Chrome, Firefox, Edge, Safari + mobile | WebKit/Gecko     | -/-                 | Yes          | Combined with apps it is around 30%. Mobile Web is not covered by Litmus tests. |
| Yahoo! Mail             | Browser  | Latest: Chrome, Firefox, Edge, Safari          | WebKit/Gecko     | 3.37/2.89           | Yes          |                                                                                 |
| Outlook.com + Office365 | Browser  | Latest: Chrome, Firefox, Edge, Safari          | WebKit/Gecko     | -/0.67              | Yes          |                                                                                 |
| Web.de                  | Browser  | Latest: Chrome, Firefox, Edge, Safari          | WebKit/Gecko     | -/-                 | Yes          | Combined usage 0.07% App + web                                                  |
| Orange.fr               | Browser  | Latest: Chrome, Firefox, Edge, Safari          | WebKit/Gecko     | -/-                 | No           | Combined usage 0.07% App + web                                                  |
| Windows Live Mail       | Browser  | Latest: Chrome, Firefox, Edge, Safari          | WebKit/Gecko     | -/0.06              | No           |                                                                                 |

### APPLICATION CLIENTS

| Client         | Platform              | Versions to Support  | Rendering Engine | Percentage (Litmus) | Litmus Check          | Note                                                                                                                                                                                                     |
| -------------- | --------------------- | -------------------- | ---------------- | ------------------- | --------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Gmail App      | iOS                   | Latest               | Proprietary      | -/-                 | Yes                   |                                                                                                                                                                                                          |
| Gmail App      | Android               | Android 7.0 - Latest | Proprietary      | -/-                 | Partially (up to 8.1) | Android 6 market share is 1.7%. Android 7 starts 4.4%                                                                                                                                                    |
| Apple Mail     | iOS                   | Latest               | WebKit           | -/-                 | Yes                   | No exact data about usage but combined iOS and desktop has almost 60% on Litmus                                                                                                                          |
| Apple Mail     | macOS                 | Latest               | WebKit           | -/-                 | Yes                   |                                                                                                                                                                                                          |
| Outlook        | Windows               | 2021, 2019, 2016     | Word             | 2.2/4.21            | Yes                   | 2006 still has 0.04% opens but 2013 officially lost support in April 2023. Not sure what user agents are used in 2021 and 2019. Maybe they report as 2016. They both still use Word as rendering engine. |
| Outlook        | macOS                 | Latest               | WebKit           | -/-                 | Yes (2016)            |                                                                                                                                                                                                          |
| Google Android | Android               | Android 7.0 - Latest | ?                | -/1.48              | Yes (only 6)          | Android 6 market share is 1.7%. Android 7 starts 4.4%                                                                                                                                                    |
| Samsung Mail   | Android               | Android 7.0 - Latest | ?                | 0.014/0.17          | Yes (Android 7)       | Android 6 market share is 1.7%. Android 7 starts 4.4%                                                                                                                                                    |
| Web.de         | iOS/Android           | Latest               | ?                | -/0.07              | No                    | Combined usage 0.07% App + web                                                                                                                                                                           |
| Orange.fr      | iOS/Android           | Latest               | ?                | -/0.07              | No                    |                                                                                                                                                                                                          |
| Thunderbird    | Windows, macOS, Linux | Latest               | Gecko            | -/0.61              | Yes                   | It uses bundled rendering engine so it should be enough to test on one platform                                                                                                                          |
| Windows Mail   | Windows               | 10, 11               | Word             | -/-                 | Yes                   | Default Client in Windows. Market share should be over 6% in desktop clients                                                                                                                             |

## Actions and Filters

These actions and filters are currently **Work-in-progress**.
We may add, update and delete any of them.

**Please use with caution**.

### Actions

| Name                              | Argument           | Description         |
| --------------------------------- | ------------------ | ------------------- |
| `woocommerce_email_editor_events` | `EventData.detail` | Email editor events |

### Filters

| Name                                                               | Argument                         | Return                                     | Description                                                                                                         |
| ------------------------------------------------------------------ | -------------------------------- | ------------------------------------------ | ------------------------------------------------------------------------------------------------------------------- |
| `woocommerce_email_editor_events_tracking_enabled`                 | `boolean` (false-default)        | `boolean`                                  | Used to enable the email editor events tracking and collection                                                      |
| `woocommerce_email_editor_wrap_editor_component`                   | `JSX.Element` Editor             | `JSX.Element` Editor                       | The main editor component. Custom component can wrap the editor and provide additional functionality                |
| `woocommerce_email_editor_send_button_label`                       | `string` 'Send'                  | `string` 'Send' (default)                  | Email editor send button label. The `Send` text can be updated using this filter                                    |
| `woocommerce_email_editor_send_action_callback`                    | `function` sendAction            | `function` sendAction                      | Action to perform when the Send button is clicked                                                                   |
| `woocommerce_email_editor_content_validation_rules`                | `array` rules                    | `EmailContentValidationRule[]` rules       | Email editor content validation rules. The validation is done on `send btton` click and revalidated on `save draft` |
| `woocommerce_email_editor_check_sending_method_configuration_link` | `string` link                    | `string` link                              | Edit or remove the sending configuration link message                                                               |
| `woocommerce_email_editor_setting_sidebar_extension_component`     | `JSX.Element` RichTextWithButton | `JSX.Element` Sidebar extension component  | Add components to the Email settings sidebar                                                                        |
| `woocommerce_email_editor_preferred_template_title`                | `string` '', `Post` post         | `string` custom (preferred) template title | Custom title for Email preset template selector                                                                     |

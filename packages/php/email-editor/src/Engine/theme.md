# Theme.json for the email editor

We use theme.json to define settings and styles for the email editor and we reuse the definitions also in the rendering engine.
The theme is used in combination with the [core's theme.json](https://github.com/WordPress/WordPress/blob/master/wp-includes/theme.json). We load the core's theme.json first and then we merge the email editor's theme.json on top of it.

In this file we want to document settings and styles that are specific to the email editor.

## Settings

- **color**: We disable gradients, because they are not supported in many email clients. We may add the support later.
- **layout**: We set content width to 660px, because it's the most common width for emails. This is meant as a default value.
- **spacing**: We allow only px units, because they are the most reliable in email clients. We may add the support for other units later with some sort of conversion to px. We also disable margins because they are not supported in our renderer (margin collapsing might be tricky).
- **border**: We want to allow all types of borders and border styles.
- **typography**: We disabled fontWeight and dropCap appearance settings, because they are not supported in our renderer. We may add the support later. We also define a set of basic font families that are safe to use with emails. The list was copied from the battle tested legacy editor.

## Styles

- **spacing**: We define default padding for the emails.
- **color**: We define default colors for text and background of the emails.
- **typography**: We define default font family and font size for the emails.

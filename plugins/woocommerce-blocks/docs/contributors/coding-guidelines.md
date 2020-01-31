# Coding Guidelines

This living document serves to prescribe coding guidelines specific to the WooCommerce Blocks project. Base coding guidelines follow the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/) and [Gutenberg coding standards](https://developer.wordpress.org/block-editor/contributors/develop/coding-guidelines/).

The following sections outline additional patterns and conventions used in the Blocks project.

## CSS

### CSS Class Names

To avoid class name collisions, class names must adhere to the following guidelines, based on the [BEM methodology](https://en.bem.info/methodology/) and [Gutenberg coding standards](https://developer.wordpress.org/block-editor/contributors/develop/coding-guidelines/).

#### Prefixing

As a WordPress plugin, Blocks has to play nicely with other plugins and themes, and WordPress itself. To minimize potential conflicts, all classes should be prefixed with `.wc-block-`.

#### Naming

All class names assigned to an element must be prefixed with the following, each joined by a dash (`-`):

-   The `wc-block` plugin prefix.
-   The name of the sub-package (where applicable, e.g. if there was a distributed sub-package called `components` living within the blocks plugin, the prefix would be `wc-block-components-`).
-   The name of the directory in which the component resides.

Any descendant of the component's root element must append a dash-delimited descriptor, separated from the base by two consecutive underscores `__`.

    Example, `assets/base/components/checkbox-list` uses the class name: `wc-block-checkbox-list`.

A **root element** (or **Block** in BEM notation) is a standalone entity that is meaningful on its own. Whilst they can be nested and interact with each other, semantically they remain equal; there is no precedence or hierarchy.

    Example: `wc-block-package-directory`

A **child element** (or **Element** in BEM notation) has no standalone meaning and is semantically tied to its block.

    Example: `wc-block-package-directory__descriptor-foo-bar`

Finally, A **modifier** is a flag on an element which can be used to change appearance, behavior or state.

    Example: `wc-block-package-directory__descriptor-foo-bar--state`

The **root element** is considered to be the highest ancestor element returned by the default export in the index.js. Notably, if your folder contains multiple files, each with their own default exported component, only the element rendered by that of index.js can be considered the root. All others should be treated as **descendants**.

Naming is not strictly tied to the DOM so it **doesn’t matter how many nested levels deep a descendant element is**. The naming convention is there to help you identify relationships with the root element.

**Nesting Example:**

-   `wc-block-dropdown-selector` (Root Element/BEM Block)
-   ├── `wc-block-dropdown-selector__input` (Child Element/BEM Element)
-   ├── `wc-block-dropdown-selector__input--hidden` (Modifier)
-   └── `wc-block-dropdown-selector__placeholder` (Child Element/BEM Element)

### RTL Styles

Blocks uses the `webpack-rtl-plugin` package to generate styles for Right-to-Left languages. These are generated automatically.

To make adjustments to the generated RTL styles, for example, excluding certain rules from the RTL stylesheets, you should use the [control directives here](https://rtlcss.com/learn/usage-guide/control-directives/index.html).

For example, you can exclude individual lines:

```css
.code {
	/*rtl:ignore*/
	direction: ltr;
	/*rtl:ignore*/
	text-align: left;
}
```

Or exclude blocks of CSS:

```css
.code {
	/*rtl:begin:ignore*/
	direction: ltr;
	text-align: left;
	/*rtl:end:ignore*/
}
```

### SCSS File Naming Conventions for Blocks

The build process will split SCSS from within the blocks library directory into two separate CSS files when Webpack runs.

Styles placed in a `style.scss` file will be built into `build/style.css`, to load on the front end theme as well as in the editor. If you need additional styles specific to the block's display in the editor, add them to an `editor.scss`.

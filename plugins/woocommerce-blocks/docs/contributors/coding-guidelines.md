# Coding Guidelines <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [CSS Class Names](#css-class-names)
    -   [Prefixing](#prefixing)
    -   [Naming](#naming)
-   [RTL Styles](#rtl-styles)
-   [SCSS File Naming Conventions for Blocks](#scss-file-naming-conventions-for-blocks)
-   [Accessible font sizes](#accessible-font-sizes)
-   [CSS specificity wars with 3rd party themes](#css-specificity-wars-with-3rd-party-themes)

This living document serves to prescribe coding guidelines specific to the WooCommerce Blocks project. Base coding guidelines follow the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/) and [Gutenberg coding standards](https://developer.wordpress.org/block-editor/contributors/develop/coding-guidelines/). The following sections outline additional patterns and conventions used in the Blocks project.

## CSS Class Names

To avoid class name collisions, class names must adhere to the following guidelines, based on the [BEM methodology](https://en.bem.info/methodology/) and [Gutenberg coding standards](https://developer.wordpress.org/block-editor/contributors/develop/coding-guidelines/).

### Prefixing

As a WordPress plugin, Blocks has to play nicely with other plugins and themes, and WordPress itself. To minimize potential conflicts, all classes should be prefixed with `.wc-block-`.

### Naming

All class names assigned to an element must be prefixed. We use different prefixes to differentiate frontend and editor elements as well as identifying if they are components or not:

-   `wc-block` for classes applied to a single block.
-   `wc-block-components` for classes applied to a component, which might be used by several blocks.
-   `wc-block-editor` for classes applied to a single block but only used in the editor UI.
-   `wc-block-editor-components` for classes applied to an editor component.

As a rule of thumb, this is the relation between location in the source tree and class name used:

| Location in the tree        | Class names used               | Can be styled by themes? |
| --------------------------- | ------------------------------ | :----------------------: |
| assets/js/atomic/blocks     | `.wc-block-components-`        |            ✓             |
| assets/js/base/components   | `.wc-block-components-`        |            ✓             |
| assets/js/blocks            | Frontend: `.wc-block-`         |            ✓             |
| assets/js/blocks            | Editor: `.wc-block-editor-`    |            ✘             |
| assets/js/editor-components | `.wc-block-editor-components-` |            ✘             |

After the prefix, class names are built using BEM:

A **root element** (or **Block** in BEM notation) is a standalone entity that is meaningful on its own. Whilst they can be nested and interact with each other, semantically they remain equal; there is no precedence or hierarchy.

Example: `wc-block-directory-name`

Any descendant of the component's root element must append a dash-delimited descriptor, separated from the base by two consecutive underscores `__`.

A **child element** (or **Element** in BEM notation) has no standalone meaning and is semantically tied to its block.

Example: `wc-block-directory-name__descriptor-foo-bar`

Finally, A **modifier** is a flag on an element which can be used to change appearance, behavior or state.

Example: `wc-block-directory-name__descriptor-foo-bar--state`

The **root element** is considered to be the highest ancestor element returned by the default export in the index.js. Notably, if your folder contains multiple files, each with their own default exported component, only the element rendered by that of index.js can be considered the root. All others should be treated as **descendants**.

Naming is not strictly tied to the DOM so it **doesn’t matter how many nested levels deep a descendant element is**. The naming convention is there to help you identify relationships with the root element.

**Nesting Example:**

-   `wc-block-components-dropdown-selector` (Root Element/BEM Block)
-   ├── `wc-block-components-dropdown-selector__input` (Child Element/BEM Element)
-   ├── `wc-block-components-dropdown-selector__input--hidden` (Modifier)
-   └── `wc-block-components-dropdown-selector__placeholder` (Child Element/BEM Element)

## RTL Styles

Blocks uses the `@automattic/webpack-rtl-plugin` package to generate styles for Right-to-Left languages. These are generated automatically.

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

## SCSS File Naming Conventions for Blocks

The build process will split SCSS from within the blocks library directory into two separate CSS files when Webpack runs.

Styles placed in a `style.scss` file will be built into `build/style.css`, to load on the front end theme as well as in the editor. If you need additional styles specific to the block's display in the editor, add them to an `editor.scss`.

## Accessible font sizes

Font sizes must be defined using the `font-size()` mixin, it takes a named size (`smaller`, `small`, `regular`, `large`. `larger`) and returns a font-size declaration in `em` units. This provides a consistent set of font sizes to be used across blocks, and by using `em` it increases the likelihood that blocks are accessible and fit better within different themes.

In parallel to that, consider whether other size/distance units in your CSS need to be em instead of px. In general, em should be preferred if it doesn't break the layout with big font sizes. There is another mixin named `em()` that helps converting px units to em (given a px size and optionally a base size).

## CSS specificity wars with 3rd party themes

We want our blocks to look good with as many themes as possible out of the box. Sometimes our styles will conflict with theme styles that have higher specificity. In these cases it may be tempting to increase the specificity of selectors, but increasing them too much makes it harder for other themes to style our blocks.

The following guidelines should help you decide _when_ to increase specificity, if at all. They are not hard rules so feel free to apply your best judgement on a case-by-case basis.

Imagine we are styling the radio control input but our styles are conflicting with some themes. For example, two themes that have the following styles:

Theme A:

```css
input[type='radio'] {
	// specificity 0, 1, 1
	background: red;
}
```

Theme B:

```css
input[type='radio']:checked {
	// specificity 0, 2, 1
	background: blue;
}
```

And these are the styles of the block:

```css
.wc-block-components-radio-control__input {
	// specificity 0, 1, 0
	background: #fff;
}
```

As you can see, the styles coming from the themes have higher specificity, so our styles would be overriden. In order to solve this:

1. Never use `!important` rules in CSS to engage in a specificity war with a theme.
2. Never use ID selectors.
3. Try wrapping the entire component/block CSS with the root class name of that component, for example:

```css
.wc-block-components-radio-control {
	.wc-block-components-radio-control__input {
		// specificity 0, 2, 0, we win theme A!
		background: #fff;
	}
}
```

4. Try adding an extra css class (or tag selector) to increase specificity. When doing so, add a comment explaining it.

```css
.wc-block-components-radio-control {
	// Extra class for specificity.
	.wc-block-components-radio-control__option
		.wc-block-components-radio-control__input {
		// specificity 0, 3, 0, we win theme B!
		background: #fff;
	}
}
```

5. If these steps weren't enough, consider not increasing specificity at all. If it's just a minor visual issue, consider ignoring it and assume the theme will update its conflicting styles at some point. If it's completely breaking the block or component in that theme, consider sending feedback to theme authors so they can fix it on their side.

Notice in the worst case scenario we would have increased selector specificity by 2 classes (0, 2, 0). That shouldn't make it too difficult for other themes to write styles on top of ours.

<!-- FEEDBACK -->

---

[We're hiring!](https://woo.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/contributors/coding-guidelines.md)

<!-- /FEEDBACK -->


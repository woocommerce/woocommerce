# CSS

## Folder/File Structure

- `/shared/*` – these are the styles that will appear in compiled CSS - the reset, gutenberg components import, etc
- `/_embedded.scss` – this file is used for the embed pages CSS, and is imported in `embedded.js` only
- `/_index.scss` – this file is used for the app's CSS, and is imported in `index.js` only

## Naming: Component classes

To avoid class name collisions between elements of the woo app and to the enclosing WordPress dashboard, class names **must** adhere to the following guidelines:

Any default export of a folder's `index.js` **must** be prefixed with `woocommerce-` followed by the directory name in which it resides:

```
.woocommerce-[ directory name ]
```

(Example: `.woocommerce-card` from `components/card/index.js`)

For any descendant of the top-level (`index.js`) element, prefix using the top-level element's class name separated by two underscores:

```
.woocommerce-[ directory name ]__[ descendant description ]
```

(Example: `.woocommerce-card__title`, or `.woocommerce-ellipsis-menu__item`)

For optional variations of an element or its descendants, you may use a modifier class, but you **must not** apply styles to the modifier class directly; only as an additional selector to the element to which the modifier applies:

```
.woocommerce-[ directory name ].is-[ modifier description ]
.woocommerce-[ directory name ]__[ descendant description ].is-[ modifier description ]
```

(Example: `.woocommerce-ellipsis-menu__item.is-active` )

In all of the above cases, except in separating the top-level element from its descendants, you **must** use dash delimiters when expressing multiple terms of a name. You can use `.is-*` or `.has-*` to describe element states.

You may observe that these conventions adhere closely to the [BEM (Blocks, Elements, Modifiers)](http://getbem.com/introduction/) CSS methodology, with minor adjustments to the application of modifiers.

## Naming: Layout classes

All layout classes use the `.woocommerce-layout__` prefix:

```
.woocommerce-layout__[ section ]
```

(Example: `.woocommerce-layout__activity-panel` )

If the section has children elements, prefix a description with the section class name:

```
.woocommerce-layout__[ section ]-[ descendant description ]
```

(Example: `.woocommerce-layout__activity-panel-title` )

## Naming: Dashboard classes

All dashboard components use the `.woocommerce-dashboard__` prefix:

```
.woocommerce-dashboard__[ section ]
```

(Example: `.woocommerce-dashboard__widget` )

## Naming: Analytics classes

All analytics components use the `.woocommerce-analytics__` prefix.

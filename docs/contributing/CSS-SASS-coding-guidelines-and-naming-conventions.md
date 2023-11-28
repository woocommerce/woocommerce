# CSS SASS coding guidelines and naming conventions

Our guidelines are based on those used in [Calypso](https://github.com/Automattic/wp-calypso) which itself follows the BEM methodology. Refer to [this doc](https://wpcalypso.wordpress.com/devdocs/docs/coding-guidelines/css.md?term=css) for full details. There are a few differences in WooCommerce however which are outlined below;

## Prefixing

As a WordPress plugin WooCommerce has to play nicely with WordPress core and other plugins / themes. To minimise conflict potential all classes should be prefixed with `.woocommerce-`.

## Class names

Calypso is built in React and uses component names to formulate CSS class names. WooCommerce Core has none of these components so uses a more traditional [BEM](http://getbem.com/) approach to [naming classes](http://cssguidelin.es/#bem-like-naming). 

When adding classes just remember;

* **Block** - Standalone entity that is meaningful on its own.
* **Element** - Parts of a block and have no standalone meaning. They are semantically tied to its block.
* **Modifier** - Flags on blocks or elements. Use them to change appearance or behaviour.

### Example

* `.woocommerce-loop {}` (block).
* `.woocommerce-loop-product {}` (nested block).
* `.woocommerce-loop-product--sale {}` (modifier).
* `.woocommerce-loop-product__link {}` (element).
* `.woocommerce-loop-product__title {}` (element).
* `.woocommerce-loop-product__price {}` (element).
* `.woocommerce-loop-product__rating {}` (element).
* `.woocommerce-loop-product__button-add-to-cart {}` (element).
* `.woocommerce-loop-product__button-add-to-cart--added {}` (modifier).

**Note:** `.woocommerce-loop-product` is not the chosen classname _because_ the block is nested within `.woocommerce-loop`. It's to be specific so that we can have separate classes for single products, cart products etc. _Nested blocks do not need to inherit their parents full name_.

You can read more about BEM key concepts [here](https://en.bem.info/methodology/key-concepts/).

#### TL;DR

* Follow the [WP Coding standards for CSS](https://make.wordpress.org/core/handbook/best-practices/coding-standards/css/) unless it contradicts anything here.
* Follow [Calypso guidelines](https://wpcalypso.wordpress.com/devdocs/docs/coding-guidelines/css.md?term=css).
* Use BEM for [class names](https://en.bem.info/methodology/naming-convention/).
* Prefix all the things.

<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS;

/**
 * Represents any entity in the CSS that is encapsulated by a class.
 *
 * Its primary purpose is to provide a type for use with `Document::getAllValues()`
 * when a subset of values from a particular part of the document is required.
 *
 * Thus, elements which don't contain `Value`s (such as statement at-rules) don't need to implement this.
 *
 * It extends `Renderable` because every element is renderable.
 */
interface CSSElement extends Renderable {}

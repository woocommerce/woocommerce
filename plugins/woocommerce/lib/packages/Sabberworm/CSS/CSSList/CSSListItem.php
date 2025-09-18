<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSList;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Comment\Commentable;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Renderable;

/**
 * Represents anything that can be in the `$contents` of a `CSSList`.
 *
 * The interface does not define any methods to implement.
 * It's purpose is to allow a single type to be specified for `CSSList::$contents` and manipulation methods thereof.
 * It extends `Commentable` and `Renderable` because all `CSSListItem`s are both.
 * This allows implementations to call methods from those interfaces without any additional type checks.
 */
interface CSSListItem extends Commentable, Renderable {}

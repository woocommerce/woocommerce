<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSList\CSSListItem;

/**
 * Note that `CSSListItem` extends both `Commentable` and `Renderable`,
 * so concrete classes implementing this interface must also implement those.
 */
interface AtRule extends CSSListItem
{
    /**
     * Since there are more set rules than block rules,
     * we’re whitelisting the block rules and have anything else be treated as a set rule.
     *
     * @var non-empty-string
     *
     * @internal since 8.5.2
     */
    public const BLOCK_RULES = 'media/document/supports/region-style/font-feature-values';

    /**
     * @return non-empty-string
     */
    public function atRuleName(): string;
}

<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSList;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property\AtRule;

class KeyFrame extends CSSList implements AtRule
{
    /**
     * @var non-empty-string
     */
    private $vendorKeyFrame = 'keyframes';

    /**
     * @var non-empty-string
     */
    private $animationName = 'none';

    /**
     * @param non-empty-string $vendorKeyFrame
     */
    public function setVendorKeyFrame(string $vendorKeyFrame): void
    {
        $this->vendorKeyFrame = $vendorKeyFrame;
    }

    /**
     * @return non-empty-string
     */
    public function getVendorKeyFrame(): string
    {
        return $this->vendorKeyFrame;
    }

    /**
     * @param non-empty-string $animationName
     */
    public function setAnimationName(string $animationName): void
    {
        $this->animationName = $animationName;
    }

    /**
     * @return non-empty-string
     */
    public function getAnimationName(): string
    {
        return $this->animationName;
    }

    /**
     * @return non-empty-string
     */
    public function render(OutputFormat $outputFormat): string
    {
        $formatter = $outputFormat->getFormatter();
        $result = $formatter->comments($this);
        $result .= "@{$this->vendorKeyFrame} {$this->animationName}{$formatter->spaceBeforeOpeningBrace()}{";
        $result .= $this->renderListContents($outputFormat);
        $result .= '}';
        return $result;
    }

    public function isRootList(): bool
    {
        return false;
    }

    /**
     * @return non-empty-string
     */
    public function atRuleName(): string
    {
        return $this->vendorKeyFrame;
    }

    /**
     * @return non-empty-string
     */
    public function atRuleArgs(): string
    {
        return $this->animationName;
    }
}

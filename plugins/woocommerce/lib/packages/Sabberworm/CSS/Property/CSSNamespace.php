<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Comment\CommentContainer;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Positionable;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value\CSSString;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value\URL;

/**
 * `CSSNamespace` represents an `@namespace` rule.
 */
class CSSNamespace implements AtRule, Positionable
{
    use CommentContainer;
    use Position;

    /**
     * @var CSSString|URL
     */
    private $url;

    /**
     * @var string|null
     */
    private $prefix;

    /**
     * @param CSSString|URL $url
     * @param int<1, max>|null $lineNumber
     */
    public function __construct($url, ?string $prefix = null, ?int $lineNumber = null)
    {
        $this->url = $url;
        $this->prefix = $prefix;
        $this->setPosition($lineNumber);
    }

    /**
     * @return non-empty-string
     */
    public function render(OutputFormat $outputFormat): string
    {
        return '@namespace ' . ($this->prefix === null ? '' : $this->prefix . ' ')
            . $this->url->render($outputFormat) . ';';
    }

    /**
     * @return CSSString|URL
     */
    public function getUrl()
    {
        return $this->url;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @param CSSString|URL $url
     */
    public function setUrl($url): void
    {
        $this->url = $url;
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * @return non-empty-string
     */
    public function atRuleName(): string
    {
        return 'namespace';
    }

    /**
     * @return array{0: CSSString|URL|non-empty-string, 1?: CSSString|URL}
     */
    public function atRuleArgs(): array
    {
        $result = [$this->url];
        if (\is_string($this->prefix) && $this->prefix !== '') {
            \array_unshift($result, $this->prefix);
        }
        return $result;
    }
}

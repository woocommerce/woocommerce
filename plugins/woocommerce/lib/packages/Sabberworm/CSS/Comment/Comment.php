<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Comment;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Renderable;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Positionable;

class Comment implements Positionable, Renderable
{
    use Position;

    /**
     * @var string
     *
     * @internal since 8.8.0
     */
    protected $commentText;

    /**
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(string $commentText = '', ?int $lineNumber = null)
    {
        $this->commentText = $commentText;
        $this->setPosition($lineNumber);
    }

    public function getComment(): string
    {
        return $this->commentText;
    }

    public function setComment(string $commentText): void
    {
        $this->commentText = $commentText;
    }

    /**
     * @return non-empty-string
     */
    public function render(OutputFormat $outputFormat): string
    {
        return '/*' . $this->commentText . '*/';
    }
}

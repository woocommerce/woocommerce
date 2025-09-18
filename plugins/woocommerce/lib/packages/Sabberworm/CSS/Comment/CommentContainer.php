<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Comment;

/**
 * Provides a standard reusable implementation of `Commentable`.
 *
 * @internal
 *
 * @phpstan-require-implements Commentable
 */
trait CommentContainer
{
    /**
     * @var list<Comment>
     */
    protected $comments = [];

    /**
     * @param list<Comment> $comments
     */
    public function addComments(array $comments): void
    {
        $this->comments = \array_merge($this->comments, $comments);
    }

    /**
     * @return list<Comment>
     */
    public function getComments(): array
    {
        return $this->comments;
    }

    /**
     * @param list<Comment> $comments
     */
    public function setComments(array $comments): void
    {
        $this->comments = $comments;
    }
}

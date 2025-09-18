<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Comment;

/**
 * A standard implementation of this interface is available in the `CommentContainer` trait.
 */
interface Commentable
{
    /**
     * @param list<Comment> $comments
     */
    public function addComments(array $comments): void;

    /**
     * @return list<Comment>
     */
    public function getComments(): array;

    /**
     * @param list<Comment> $comments
     */
    public function setComments(array $comments): void;
}

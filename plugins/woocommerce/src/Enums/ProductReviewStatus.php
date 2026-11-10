<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the moderation statuses of a product review, as the review abilities name them.
 *
 * WordPress stores them differently in `comment_approved`: `1`, `0`, `spam` and `trash`.
 *
 * @since 11.4.0
 */
final class ProductReviewStatus {
	/**
	 * The review is published on the product page.
	 *
	 * @var string
	 */
	public const APPROVED = 'approved';

	/**
	 * The review is awaiting moderation.
	 *
	 * @var string
	 */
	public const HOLD = 'hold';

	/**
	 * The review is marked as spam.
	 *
	 * @var string
	 */
	public const SPAM = 'spam';

	/**
	 * The review is in the trash.
	 *
	 * @var string
	 */
	public const TRASH = 'trash';
}

<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for all the product statuses.
 */
final class ProductStatus {
	/**
	 * The product is in auto-draft status.
	 *
	 * @var string
	 */
	public const AUTO_DRAFT = 'auto-draft';

	/**
	 * The product is in draft status.
	 *
	 * @var string
	 */
	public const DRAFT = 'draft';

	/**
	 * The product is in pending status.
	 *
	 * @var string
	 */
	public const PENDING = 'pending';

	/**
	 * The product is in private status.
	 *
	 * @var string
	 */
	public const PRIVATE = 'private';

	/**
	 * The product is in publish status.
	 *
	 * @var string
	 */
	public const PUBLISH = 'publish';

	/**
	 * The product is in trash status.
	 *
	 * @var string
	 */
	public const TRASH = 'trash';

	/**
	 * The product is in future status.
	 *
	 * @var string
	 */
	public const FUTURE = 'future';

	/**
	 * Returns every product status value defined by this enum, as a flat list.
	 *
	 * All of these can be set on a product: WC_Product::set_status() does not validate, and core
	 * assigns self::AUTO_DRAFT and self::TRASH itself. The distinction is who chooses them --
	 * self::AUTO_DRAFT, self::TRASH and self::FUTURE are driven by WordPress rather than picked by
	 * an author, so they are not offered everywhere a status is.
	 *
	 * WooCommerce has no narrower helper, and get_post_statuses() is not one: it is unfiltered and
	 * omits self::FUTURE. Callers that need only what a particular editor or REST schema exposes
	 * should narrow this list themselves.
	 *
	 * @since 10.9.0
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return array(
			self::AUTO_DRAFT,
			self::DRAFT,
			self::PENDING,
			self::PRIVATE,
			self::PUBLISH,
			self::TRASH,
			self::FUTURE,
		);
	}
}

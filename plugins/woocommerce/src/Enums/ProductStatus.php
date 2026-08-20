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
	 * Returns every product status value defined by this enum.
	 *
	 * This is the complete enum, including statuses WordPress manages rather than ones an author
	 * chooses: self::AUTO_DRAFT, self::TRASH and self::FUTURE. WooCommerce has no narrower helper,
	 * so callers that need only the editorial statuses should narrow this list themselves.
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

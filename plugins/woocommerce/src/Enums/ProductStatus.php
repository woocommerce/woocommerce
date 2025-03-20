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
	const AUTO_DRAFT = 'auto-draft';

	/**
	 * The product is in draft status.
	 *
	 * @var string
	 */
	const DRAFT = 'draft';

	/**
	 * The product is in pending status.
	 *
	 * @var string
	 */
	const PENDING = 'pending';

	/**
	 * The product is in private status.
	 *
	 * @var string
	 */
	const PRIVATE = 'private';

	/**
	 * The product is in publish status.
	 *
	 * @var string
	 */
	const PUBLISH = 'publish';

	/**
	 * The product is in trash status.
	 *
	 * @var string
	 */
	const TRASH = 'trash';

	/**
	 * The product is in future status.
	 *
	 * @var string
	 */
	const FUTURE = 'future';
}

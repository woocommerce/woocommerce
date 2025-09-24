<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for feature plugin compatibility.
 */
final class FeaturePluginCompatibility {

	/**
	 * Plugins are compatible by default with the feature.
	 *
	 * @var string
	 */
	const COMPATIBLE = 'compatible';

	/**
	 * Plugins are incompatible by default with the feature.
	 *
	 * @var string
	 */
	const INCOMPATIBLE = 'incompatible';

	/**
	 * Plugin compatibility with the feautre is yet to be determined. Internal use only.
	 *
	 * @internal
	 * @var string
	 */
	const UNCERTAIN = 'uncertain';

	/**
	 * Valid values for registration of feature compatibility.
	 *
	 * @var string[]
	 */
	const VALID_REGISTRATION_VALUES = array(
		self::COMPATIBLE,
		self::INCOMPATIBLE,
	);
}

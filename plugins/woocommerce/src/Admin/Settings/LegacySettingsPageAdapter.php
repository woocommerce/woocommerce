<?php
/**
 * Public legacy WC_Settings_Page adapter for modern settings.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Adapts a WC_Settings_Page instance into the modern settings page contract.
 *
 * Extensions can use this class directly for native-field migrations, or
 * subclass it to add component metadata, script handles, or custom save behavior.
 *
 * @since 10.8.0
 */
class LegacySettingsPageAdapter extends \Automattic\WooCommerce\Internal\Admin\Settings\LegacySettingsPageAdapter {}

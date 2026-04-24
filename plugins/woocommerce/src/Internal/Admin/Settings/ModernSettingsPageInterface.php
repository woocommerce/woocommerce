<?php
/**
 * Modern settings page contract.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Admin\Settings\ModernSettingsPageInterface as PublicModernSettingsPageInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Internal alias for the public modern settings page contract.
 *
 * @since 10.8.0
 */
interface ModernSettingsPageInterface extends PublicModernSettingsPageInterface {}

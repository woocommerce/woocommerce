<?php
/**
 * Settings UI page contract.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface as PublicSettingsUIPageInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Internal alias for the public settings UI page contract.
 *
 * @since 10.9.0
 * @deprecated 11.4.0 The Settings UI was removed. Settings pages use the classic renderer.
 */
interface SettingsUIPageInterface extends PublicSettingsUIPageInterface {}

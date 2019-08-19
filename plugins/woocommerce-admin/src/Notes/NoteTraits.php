<?php
/**
 * WC Admin Note Traits
 *
 * WC Admin Note Traits class that houses shared functionality across notes.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * NoteTraits class.
 */
trait NoteTraits {
    /**
     * Test how long WooCommerce Admin has been active.
     *
     * @param int $seconds Time in seconds to check.
     * @return bool Whether or not WooCommerce admin has been active for $seconds.
     */
    public static function wc_admin_active_for( $seconds ) {
        // Getting install timestamp reference class-wc-admin-install.php.
		$wc_admin_installed = get_option( 'wc_admin_install_timestamp', false );

        if ( false === $wc_admin_installed ) {
            update_option( 'wc_admin_install_timestamp', time() );
            
            return false;
		}

        return ( ( time() - $wc_admin_installed ) >= $seconds );
    }
}

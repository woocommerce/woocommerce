<?php
/**
 * Show details about updates
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Admin_Updates' ) ) :

/**
 * WC_Admin_Updates Class
 */
class WC_Admin_Updates {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'in_plugin_update_message-woocommerce/woocommerce.php', array( $this, 'in_plugin_update_message' ) );
	}

    /**
     * Active plugins pre update option filter
     *
     * @param string $new_value
     * @return string
     */
    function pre_update_option_active_plugins($new_value) {
        $old_value = (array) get_option('active_plugins');

        if ($new_value !== $old_value && in_array(W3TC_FILE, (array) $new_value) && in_array(W3TC_FILE, (array) $old_value)) {
                $this->_config->set('notes.plugins_updated', true);
                try {
                    $this->_config->save();
                } catch(Exception $ex) {}
        }

        return $new_value;
    }

    /**
     * Show plugin changes. Code adapted from W3 Total Cache.
     *
     * @return void
     */
    function in_plugin_update_message() {
        $response = wp_remote_get( 'http://plugins.svn.wordpress.org/woocommerce/trunk/readme.txt' );

        if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {

           	// Output Upgrade Notice
            $matches = null;
            $regexp = '~==\s*Upgrade Notice\s*==\s*=\s*[0-9.]+\s*=(.*)(=\s*' . preg_quote( WOOCOMMERCE_VERSION ) . '\s*=|$)~Uis';

            if ( preg_match( $regexp, $response['body'], $matches ) ) {
                $notices = (array) preg_split('~[\r\n]+~', trim( $matches[1] ) );

                echo '<div style="font-weight: normal; background: #cc99c2; color: #fff !important; border: 1px solid #b76ca9; padding: 9px; margin: 9px 0;">';

                foreach  ( $notices as $index => $line ) {
                    echo '<p style="margin: 0; font-size: 1.1em; color: #fff; text-shadow: 0 1px 1px #b574a8;">' . preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line ) . '</p>';
                }

                echo '</div>';
            }

        	// Output Changelog
            $matches = null;
            $regexp = '~==\s*Changelog\s*==\s*=\s*[0-9.]+\s*-(.*)=(.*)(=\s*' . preg_quote( WOOCOMMERCE_VERSION ) . '\s*-(.*)=|$)~Uis';

            if ( preg_match( $regexp, $response['body'], $matches ) ) {
                $changelog = (array) preg_split('~[\r\n]+~', trim( $matches[2] ) );

                echo ' ' . __( 'What\'s new:', 'woocommerce' ) . '<div style="font-weight: normal;">';

                $ul = false;

                foreach  ( $changelog as $index => $line ) {
                    if ( preg_match('~^\s*\*\s*~', $line ) ) {
                        if ( ! $ul ) {
                            echo '<ul style="list-style: disc inside; margin: 9px 0 9px 20px; overflow:hidden; zoom: 1;">';
                            $ul = true;
                        }
                        $line = preg_replace( '~^\s*\*\s*~', '', htmlspecialchars( $line ) );
                        echo '<li style="width: 50%; margin: 0; float: left; ' . ( $index % 2 == 0 ? 'clear: left;' : '' ) . '">' . $line . '</li>';
                    } else {
                        if ( $ul ) {
                            echo '</ul>';
                            $ul = false;
                        }
                        echo '<p style="margin: 9px 0;">' . htmlspecialchars( $line ) . '</p>';
                    }
                }

                if ($ul)
                    echo '</ul>';

                echo '</div>';
            }
        }
    }


}

endif;

return new WC_Admin_Updates();
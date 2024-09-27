<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ComingSoon;

use Automattic\WooCommerce\Utilities\FeaturesUtil;


/**
 * Adds hooks to add a badge to the WordPress admin bar showing site visibility.
 */
class ComingSoonAdminBarBadge {

	/**
	 * Sets up the hooks.
	 *
	 * @internal
	 */
	final public function init() {
		add_action( 'admin_bar_menu', array( $this, 'site_visibility_badge' ), 31 );
		add_action( 'wp_head', array( $this, 'output_css' ) );
		add_action( 'admin_head', array( $this, 'output_css' ) );
	}

	/**
	 * Add site visibility cache badge to WP admin bar.
	 *
	 * @internal
	 * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
	 */
	public function site_visibility_badge( $wp_admin_bar ) {
		// Early exit if LYS feature is disabled.
		if ( ! FeaturesUtil::feature_is_enabled( 'site_visibility_badge' ) ) {
			return;
		}

		$labels = array(
			'coming-soon'       => __( 'Coming soon', 'woocommerce' ),
			'store-coming-soon' => __( 'Store coming soon', 'woocommerce' ),
			'live'              => __( 'Live', 'woocommerce' ),
		);

		if ( get_option( 'woocommerce_coming_soon' ) === 'yes' ) {
			if ( get_option( 'woocommerce_store_pages_only' ) === 'yes' ) {
				$key = 'store-coming-soon';
			} else {
				$key = 'coming-soon';
			}
		} else {
			$key = 'live';
		}

		$args = array(
			'id'    => 'woocommerce-site-visibility-badge',
			'title' => $labels[ $key ],
			'href'  => admin_url( 'admin.php?page=wc-settings&tab=site-visibility' ),
			'meta'  => array(
				'class' => 'woocommerce-site-status-badge-' . $key,
			),
		);
		$wp_admin_bar->add_node( $args );
	}

	/**
	 * Output CSS for site visibility badge.
	 *
	 * @internal
	 */
	public function output_css() {
		// Early exit if LYS feature is disabled.
		if ( ! FeaturesUtil::feature_is_enabled( 'site_visibility_badge' ) ) {
			return;
		}

		if ( is_admin_bar_showing() ) {
			echo '<style>
				#wpadminbar .quicklinks #wp-admin-bar-woocommerce-site-visibility-badge {
					padding: 7px 0;
				}

				#wpadminbar .quicklinks #wp-admin-bar-woocommerce-site-visibility-badge a.ab-item {
					/* Layout  */
					background-color: #F6F7F7;
					border-radius: 2px;
					display: flex;
					height: 18px;
					padding: 0px 6px;
					align-items: center;
					gap: 8px;

					/* Typography  */
					color: #3C434A;
					font-size: 12px;
					font-style: normal;
					font-weight: 500;
					line-height: 16px;
				}

				#wpadminbar .quicklinks #wp-admin-bar-woocommerce-site-visibility-badge a.ab-item:hover,
				#wpadminbar .quicklinks #wp-admin-bar-woocommerce-site-visibility-badge a.ab-item:focus {
					background-color: #DCDCDE;
				}

				#wpadminbar .quicklinks #wp-admin-bar-woocommerce-site-visibility-badge a.ab-item:focus {
					outline: var(--wp-admin-border-width-focus) solid var(--wp-admin-theme-color-darker-20);
				}

				#wpadminbar .quicklinks #wp-admin-bar-woocommerce-site-visibility-badge.woocommerce-site-status-badge-live a.ab-item {
					background-color: #E6F2E8;
					color: #00450C;
				}

				#wpadminbar .quicklinks #wp-admin-bar-woocommerce-site-visibility-badge.woocommerce-site-status-badge-live a.ab-item:hover,
				#wpadminbar .quicklinks #wp-admin-bar-woocommerce-site-visibility-badge.woocommerce-site-status-badge-live a.ab-item:focus {
					background-color: #B8E6BF;
				}
			</style>';
		}
	}
}

add_action( 'show_user_profile', '\Automattic\WooCommerce\Internal\ComingSoon\custom_user_profile_field' );
add_action( 'edit_user_profile', '\Automattic\WooCommerce\Internal\ComingSoon\custom_user_profile_field' );

function custom_user_profile_field( $user ) {
    ?>
	<br />
    <h3><?php _e('WooCommerce Options', 'textdomain'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="custom_option"><?php _e('Toolbar', 'textdomain'); ?></label></th>
            <td>
                <label><input type="checkbox" name="custom_option" id="custom_option" value="<?php echo esc_attr( get_user_meta( $user->ID, 'custom_option', true ) ); ?>" class="regular-text" /> Show the site visibility badge in toolbar.</label>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'personal_options_update', '\Automattic\WooCommerce\Internal\ComingSoon\save_custom_user_profile_field' );
add_action( 'edit_user_profile_update', '\Automattic\WooCommerce\Internal\ComingSoon\save_custom_user_profile_field' );

function save_custom_user_profile_field( $user_id ) {
    if ( !current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }
    update_user_meta( $user_id, 'custom_option', sanitize_text_field( $_POST['custom_option'] ) );
}


add_action( 'personal_options', '\Automattic\WooCommerce\Internal\ComingSoon\custom_show_badge_option' );

function custom_show_badge_option( $user ) {
    ?>
    <tr>
        <th scope="row"><?php _e('Site Visibility Badge', 'textdomain'); ?></th>
        <td>
            <label for="show_badge">
                <input type="checkbox" name="show_badge" id="show_badge" value="1" <?php checked( get_user_meta( $user->ID, 'show_badge', true ), 1 ); ?> />
                <?php _e('Show WooCommerce site visibility badge in Toolbar', 'textdomain'); ?>
            </label>
        </td>
    </tr>
    <?php
}

add_action( 'personal_options_update', 'save_show_badge_option' );
add_action( 'edit_user_profile_update', 'save_show_badge_option' );

function save_show_badge_option( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }

    $show_badge = isset( $_POST['show_badge'] ) ? 1 : 0;
    update_user_meta( $user_id, 'show_badge', $show_badge );
}

<?php
/**
 * Addons Page
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Addons Class.
 */
class WC_Admin_Addons {

	/**
	 * Get featured for the addons screen
	 *
	 * @return array of objects
	 */
	public static function get_featured() {
		if ( false === ( $featured = get_transient( 'wc_addons_featured' ) ) ) {
			$raw_featured = wp_safe_remote_get( 'https://d3t0oesq8995hv.cloudfront.net/add-ons/featured.json', array( 'user-agent' => 'WooCommerce Addons Page' ) );
			if ( ! is_wp_error( $raw_featured ) ) {
				$featured = json_decode( wp_remote_retrieve_body( $raw_featured ) );
				if ( $featured ) {
					set_transient( 'wc_addons_featured', $featured, WEEK_IN_SECONDS );
				}
			}
		}

		if ( is_object( $featured ) ) {
			self::output_featured_sections( $featured->sections );
			return $featured;
		}
	}

	/**
	 * Get sections for the addons screen
	 *
	 * @return array of objects
	 */
	public static function get_sections() {
		if ( false === ( $sections = get_transient( 'wc_addons_sections' ) ) ) {
			$raw_sections = wp_safe_remote_get( 'https://d3t0oesq8995hv.cloudfront.net/addon-sections.json', array( 'user-agent' => 'WooCommerce Addons Page' ) );
			if ( ! is_wp_error( $raw_sections ) ) {
				$sections = json_decode( wp_remote_retrieve_body( $raw_sections ) );

				if ( $sections ) {
					set_transient( 'wc_addons_sections', $sections, WEEK_IN_SECONDS );
				}
			}
		}

		$addon_sections = array();

		if ( $sections ) {
			foreach ( $sections as $sections_id => $section ) {
				if ( empty( $sections_id ) ) {
					continue;
				}
				$addon_sections[ $sections_id ]           = new stdClass;
				$addon_sections[ $sections_id ]->title    = wc_clean( $section->title );
				$addon_sections[ $sections_id ]->endpoint = wc_clean( $section->endpoint );
			}
		}

		return apply_filters( 'woocommerce_addons_sections', $addon_sections );
	}

	/**
	 * Get section for the addons screen.
	 *
	 * @param  string $section_id
	 *
	 * @return object|bool
	 */
	public static function get_section( $section_id ) {
		$sections = self::get_sections();
		if ( isset( $sections[ $section_id ] ) ) {
			return $sections[ $section_id ];
		}
		return false;
	}

	/**
	 * Get section content for the addons screen.
	 *
	 * @param  string $section_id
	 *
	 * @return array
	 */
	public static function get_section_data( $section_id ) {
		$section      = self::get_section( $section_id );
		$section_data = '';

		if ( ! empty( $section->endpoint ) ) {
			if ( false === ( $section_data = get_transient( 'wc_addons_section_' . $section_id ) ) ) {
				$raw_section = wp_safe_remote_get( esc_url_raw( $section->endpoint ), array( 'user-agent' => 'WooCommerce Addons Page' ) );

				if ( ! is_wp_error( $raw_section ) ) {
					$section_data = json_decode( wp_remote_retrieve_body( $raw_section ) );

					if ( ! empty( $section_data->products ) ) {
						set_transient( 'wc_addons_section_' . $section_id, $section_data, WEEK_IN_SECONDS );
					}
				}
			}
		}

		return apply_filters( 'woocommerce_addons_section_data', $section_data->products, $section_id );
	}

	/**
	 * Handles the outputting of a contextually aware Storefront link (points to child themes if Storefront is already active).
	 */
	public static function output_storefront_button() {
		$template   = get_option( 'template' );
		$stylesheet = get_option( 'stylesheet' );

		if ( 'storefront' === $template ) {
			if ( 'storefront' === $stylesheet ) {
				$url         = 'https://woocommerce.com/product-category/themes/storefront-child-theme-themes/';
				$text        = __( 'Need a fresh look? Try Storefront child themes', 'woocommerce' );
				$utm_content = 'nostorefrontchildtheme';
			} else {
				$url         = 'https://woocommerce.com/product-category/themes/storefront-child-theme-themes/';
				$text        = __( 'View more Storefront child themes', 'woocommerce' );
				$utm_content = 'hasstorefrontchildtheme';
			}
		} else {
			$url         = 'https://woocommerce.com/storefront/';
			$text        = __( 'Need a theme? Try Storefront', 'woocommerce' );
			$utm_content = 'nostorefront';
		}

		$url = add_query_arg( array(
			'utm_source'   => 'addons',
			'utm_medium'   => 'product',
			'utm_campaign' => 'woocommerceplugin',
			'utm_content'  => $utm_content,
		), $url );

		echo '<a href="' . esc_url( $url ) . '" class="add-new-h2">' . esc_html( $text ) . '</a>' . "\n";
	}

	/**
	 * Handles the outputting of a banner block.
	 *
	 * @param object $block
	 */
	public static function output_banner_block( $block ) {
		?>
		<div class="addons-banner-block">
			<h1><?php echo esc_html( $block->title ); ?></h1>
			<p><?php echo esc_html( $block->description ); ?></p>
			<div class="addons-banner-block-items">
				<?php foreach ( $block->items as $item ) : ?>
					<div class="addons-banner-block-item">
						<div class="addons-banner-block-item-icon">
							<img class="addons-img" src="<?php echo esc_url( $item->image ); ?>" />
						</div>
						<div class="addons-banner-block-item-content">
							<h3><?php echo esc_html( $item->title ); ?></h3>
							<p><?php echo esc_html( $item->description ); ?></p>
							<?php
								self::output_button(
									$item->href,
									$item->button,
									'addons-button-solid',
									$item->plugin
								);
							?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles the outputting of a column.
	 *
	 * @param object $block
	 */
	public static function output_column( $block ) {
		if ( isset( $block->container ) && 'column_container_start' === $block->container ) {
			?>
			<div class="addons-column-section">
			<?php
		}
		if ( 'column_start' === $block->module ) {
			?>
			<div class="addons-column">
			<?php
		} else {
			?>
			</div>
			<?php
		}
		if ( isset( $block->container ) && 'column_container_end' === $block->container ) {
			?>
			</div>
			<?php
		}
	}

	/**
	 * Handles the outputting of a column block.
	 *
	 * @param object $block
	 */
	public static function output_column_block( $block ) {
		?>
		<div class="addons-column-block">
			<h1><?php echo esc_html( $block->title ); ?></h1>
			<p><?php echo esc_html( $block->description ); ?></p>
			<?php foreach ( $block->items as $item ) : ?>
				<div class="addons-column-block-item">
					<div class="addons-column-block-item-icon">
						<img class="addons-img" src="<?php echo esc_url( $item->image ); ?>" />
					</div>

					<div class="addons-column-block-item-content">
						<h2><?php echo esc_html( $item->title ); ?></h2>
						<?php
							self::output_button(
								$item->href,
								$item->button,
								'addons-button-solid',
								$item->plugin
							);
						?>
						<p><?php echo esc_html( $item->description ); ?></p>

					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<?php
	}

	/**
	 * Handles the outputting of a small light block.
	 *
	 * @param object $block
	 */
	public static function output_small_light_block( $block ) {
		?>
		<div class="addons-small-light-block">
			<img class="addons-img" src="<?php echo esc_url( $block->image ) ?>" />
			<div class="addons-small-light-block-content">
				<h1><?php echo esc_html( $block->title ); ?></h1>
				<p><?php echo esc_html( $block->description ); ?></p>
				<div class="addons-small-light-block-buttons">
					<?php foreach ( $block->buttons as $button ) : ?>
						<?php
							self::output_button(
								$button->href,
								$button->text,
								'addons-button-solid'
							);
						?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles the outputting of a small dark block.
	 *
	 * @param object $block
	 */
	public static function output_small_dark_block( $block ) {
		?>
		<div class="addons-small-dark-block">
			<h1><?php echo esc_html( $block->title ); ?></h1>
			<p><?php echo esc_html( $block->description ); ?></p>
			<div class="addons-small-dark-items">
				<?php foreach ( $block->items as $item ) : ?>
					<div class="addons-small-dark-item">
						<?php if ( ! empty( $item->image ) ) : ?>
							<div class="addons-small-dark-item-icon">
								<img class="addons-img" src="<?php echo esc_url( $item->image ); ?>" />
							</div>
						<?php endif; ?>
						<?php
							self::output_button(
								$item->href,
								$item->button,
								'addons-button-outline-white'
							);
						?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles the outputting of featured sections
	 *
	 * @param array $sections
	 */
	public static function output_featured_sections( $sections ) {
		foreach ( $sections as $section ) {
			switch ( $section->module ) {
				case 'banner_block':
					self::output_banner_block( $section );
					break;
				case 'column_start':
					self::output_column( $section );
					break;
				case 'column_end':
					self::output_column( $section );
					break;
				case 'column_block':
					self::output_column_block( $section );
					break;
				case 'small_light_block':
					self::output_small_light_block( $section );
					break;
				case 'small_dark_block':
					self::output_small_dark_block( $section );
					break;
			}
		}
	}

	/**
	 * Outputs a button.
	 *
	 * @param string $url
	 * @param string $text
	 * @param string $theme
	 * @param string $plugin
	 */
	public static function output_button( $url, $text, $theme, $plugin = '' ) {
		$theme = __( 'Free', 'woocommerce' ) === $text ? 'addons-button-outline-green' : $theme;
		$theme = is_plugin_active( $plugin ) ? 'addons-button-installed' : $theme;
		$text = is_plugin_active( $plugin ) ? __( 'Installed', 'woocommerce' ) : $text;
		?>
		<a
			class="addons-button <?php echo esc_attr( $theme ); ?>"
			href="<?php echo esc_url( $url ); ?>">
			<?php echo esc_html( $text ); ?>
		</a>
		<?php
	}


	/**
	 * Handles output of the addons page in admin.
	 */
	public static function output() {
		$sections        = self::get_sections();
		$theme           = wp_get_theme();
		$section_keys    = array_keys( $sections );
		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : current( $section_keys );
		include_once( dirname( __FILE__ ) . '/views/html-admin-page-addons.php' );
	}
}

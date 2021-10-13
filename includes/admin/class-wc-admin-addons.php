<?php
/**
 * Addons Page
 *
 * @package  WooCommerce\Admin
 * @version  2.5.0
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications as PromotionRuleEngine;

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
	 * @return void
	 */
	public static function render_featured() {
		$featured = get_transient( 'wc_addons_featured' );
		if ( false === $featured ) {
			$headers = array();
			$auth    = WC_Helper_Options::get( 'auth' );

			if ( ! empty( $auth['access_token'] ) ) {
				$headers['Authorization'] = 'Bearer ' . $auth['access_token'];
			}

			$parameter_string = '';
			$country  = WC()->countries->get_base_country();
			if ( ! empty( $country ) ) {
				$parameter_string = '?' . http_build_query( array( 'country' => $country ) );
			}

			// Important: WCCOM Extensions API v2.0 is used.
			$raw_featured = wp_safe_remote_get(
				'https://woocommerce.com/wp-json/wccom-extensions/2.0/featured' . $parameter_string,
				array(
					'headers'    => $headers,
					'user-agent' => 'WooCommerce Addons Page',
				)
			);

			if ( ! is_wp_error( $raw_featured ) ) {
				$featured = json_decode( wp_remote_retrieve_body( $raw_featured ) );
				if ( $featured ) {
					set_transient( 'wc_addons_featured', $featured, DAY_IN_SECONDS );
				}
			}
		}

		if ( ! empty( $featured ) ) {
			self::output_featured( $featured );
		}
	}

	/**
	 * Build url parameter string
	 *
	 * @param  string $category Addon (sub) category.
	 * @param  string $term     Search terms.
	 * @param  string $country  Store country.
	 *
	 * @return string url parameter string
	 */
	public static function build_parameter_string( $category, $term, $country ) {

		$parameters = array(
			'category' => $category,
			'term'     => $term,
			'country'  => $country,
		);

		return '?' . http_build_query( $parameters );
	}

	/**
	 * Call API to get extensions
	 *
	 * @param  string $category Addon (sub) category.
	 * @param  string $term     Search terms.
	 * @param  string $country  Store country.
	 *
	 * @return object of extensions and promotions.
	 */
	public static function get_extension_data( $category, $term, $country ) {
		$parameters = self::build_parameter_string( $category, $term, $country );

		$headers = array();
		$auth    = WC_Helper_Options::get( 'auth' );

		if ( ! empty( $auth['access_token'] ) ) {
			$headers['Authorization'] = 'Bearer ' . $auth['access_token'];
		}

		$raw_extensions = wp_safe_remote_get(
			'https://woocommerce.com/wp-json/wccom-extensions/1.0/search' . $parameters,
			array( 'headers' => $headers )
		);

		if ( ! is_wp_error( $raw_extensions ) ) {
			$addons = json_decode( wp_remote_retrieve_body( $raw_extensions ) );
		}
		return $addons;
	}

	/**
	 * Get sections for the addons screen
	 *
	 * @return array of objects
	 */
	public static function get_sections() {
		$addon_sections = get_transient( 'wc_addons_sections' );
		if ( false === ( $addon_sections ) ) {
			$raw_sections = wp_safe_remote_get(
				'https://woocommerce.com/wp-json/wccom-extensions/1.0/categories'
			);
			if ( ! is_wp_error( $raw_sections ) ) {
				$addon_sections = json_decode( wp_remote_retrieve_body( $raw_sections ) );
				if ( $addon_sections ) {
					set_transient( 'wc_addons_sections', $addon_sections, WEEK_IN_SECONDS );
				}
			}
		}
		return apply_filters( 'woocommerce_addons_sections', $addon_sections );
	}

	/**
	 * Handles the outputting of featured page
	 *
	 * @param array $blocks Featured page's blocks.
	 */
	private static function output_featured( $blocks ) {
		foreach ( $blocks as $block ) {
			$block_type = $block->type ?? null;
			switch ( $block_type ) {
				case 'group':
					self::output_group( $block );
					break;
				case 'banner':
					self::output_banner( $block );
					break;
			}
		}
	}

	/**
	 * Render a group block including products
	 *
	 * @param mixed $block Block of the page for rendering.
	 *
	 * @return void
	 */
	private static function output_group( $block ) {
		$capacity           = $block->capacity ?? 3;
		$product_list_classes = 3 === $capacity ? 'three-column' : 'two-column';
		$product_list_classes = 'products addons-products-' . $product_list_classes;
		?>
			<section class="addon-product-group">
				<h1 class="addon-product-group-title"><?php echo esc_html( $block->title ); ?></h1>
				<div class="addon-product-group-description-container">
					<?php if ( ! empty( $block->description ) ) : ?>
					<div class="addon-product-group-description">
						<?php echo esc_html( $block->description ); ?>
					</div>
					<?php endif; ?>
					<?php if ( null !== $block->url ) : ?>
					<a class="addon-product-group-see-more" href="<?php echo esc_url( $block->url ); ?>">
						<?php esc_html_e( 'See more', 'woocommerce' ); ?>
					</a>
					<?php endif; ?>
				</div>
				<div class="addon-product-group__items">
					<ul class="<?php echo esc_attr( $product_list_classes ); ?>">
					<?php
					$products = array_slice( $block->items, 0, $capacity );
					foreach ( $products as $item ) {
						self::render_product_card( $item );
					}
					?>
					</ul>
				<div>
			</section>
		<?php
	}

	/**
	 * Render a banner contains a product
	 *
	 * @param mixed $block Block of the page for rendering.
	 *
	 * @return void
	 */
	private static function output_banner( $block ) {
		if ( empty( $block->buttons ) ) {
			// Render a product-like banner.
			?>
			<ul class="products">
				<?php self::render_product_card( $block, $block->type ); ?>
			</ul>
			<?php
		} else {
			// Render a banner with buttons.
			?>
			<ul class="products">
				<li class="product addons-buttons-banner">
					<div class="addons-buttons-banner-image"
						style="background-image:url(<?php echo esc_url( $block->image ); ?>)"
						title="<?php echo esc_attr( $block->image_alt ); ?>"></div>
					<div class="product-details addons-buttons-banner-details-container">
						<div class="addons-buttons-banner-details">
							<h2><?php echo esc_html( $block->title ); ?></h2>
							<p><?php echo wp_kses( $block->description, array() ); ?></p>
						</div>
						<div class="addons-buttons-banner-button-container">
						<?php
						foreach ( $block->buttons as $button ) {
							$button_classes = array( 'button', 'addons-buttons-banner-button' );
							$type = $button->type ?? null;
							if ( 'primary' === $type ) {
								$button_classes[] = 'addons-buttons-banner-button-primary';
							}
							?>
							<a class="<?php echo esc_attr( implode( ' ', $button_classes ) ); ?>"
								href="<?php echo esc_url( $button->href ); ?>">
								<?php echo esc_html( $button->title ); ?>
							</a>
						<?php } ?>
						</div>
					</div>
				</li>
			</ul>
			<?php
		}
	}

	/**
	 * Returns in-app-purchase URL params.
	 */
	public static function get_in_app_purchase_url_params() {
		// Get url (from path onward) for the current page,
		// so WCCOM "back" link returns user to where they were.
		$back_admin_path = add_query_arg( array() );
		return array(
			'wccom-site'          => site_url(),
			'wccom-back'          => rawurlencode( $back_admin_path ),
			'wccom-woo-version'   => Constants::get_constant( 'WC_VERSION' ),
			'wccom-connect-nonce' => wp_create_nonce( 'connect' ),
		);
	}

	/**
	 * Add in-app-purchase URL params to link.
	 *
	 * Adds various url parameters to a url to support a streamlined
	 * flow for obtaining and setting up WooCommerce extensons.
	 *
	 * @param string $url    Destination URL.
	 */
	public static function add_in_app_purchase_url_params( $url ) {
		return add_query_arg(
			self::get_in_app_purchase_url_params(),
			$url
		);
	}

	/**
	 * Outputs a button.
	 *
	 * @param string $url    Destination URL.
	 * @param string $text   Button label text.
	 * @param string $style  Button style class.
	 * @param string $plugin The plugin the button is promoting.
	 */
	public static function output_button( $url, $text, $style, $plugin = '' ) {
		$style = __( 'Free', 'woocommerce' ) === $text ? 'addons-button-outline-purple' : $style;
		$style = is_plugin_active( $plugin ) ? 'addons-button-installed' : $style;
		$text  = is_plugin_active( $plugin ) ? __( 'Installed', 'woocommerce' ) : $text;
		$url   = self::add_in_app_purchase_url_params( $url );
		?>
		<a
			class="addons-button <?php echo esc_attr( $style ); ?>"
			href="<?php echo esc_url( $url ); ?>">
			<?php echo esc_html( $text ); ?>
		</a>
		<?php
	}

	/**
	 * Output HTML for a promotion action.
	 *
	 * @param array $action Array of action properties.
	 * @return void
	 */
	public static function output_promotion_action( array $action ) {
		if ( empty( $action ) ) {
			return;
		}
		$style = ( ! empty( $action['primary'] ) && $action['primary'] ) ? 'addons-button-solid' : 'addons-button-outline-purple';
		?>
		<a
			class="addons-button <?php echo esc_attr( $style ); ?>"
			href="<?php echo esc_url( $action['url'] ); ?>">
			<?php echo esc_html( $action['label'] ); ?>
		</a>
		<?php
	}


	/**
	 * Handles output of the addons page in admin.
	 */
	public static function output() {
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '_featured';
		$search  = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';

		if ( isset( $_GET['section'] ) && 'helper' === $_GET['section'] ) {
			do_action( 'woocommerce_helper_output' );
			return;
		}

		if ( isset( $_GET['install-addon'] ) ) {
			switch ( $_GET['install-addon'] ) {
				case 'woocommerce-services':
					self::install_woocommerce_services_addon();
					break;
				case 'woocommerce-payments':
					self::install_woocommerce_payments_addon( $section );
					break;
				default:
					// Do nothing.
					break;
			}
		}

		$sections        = self::get_sections();
		$theme           = wp_get_theme();
		$current_section = isset( $_GET['section'] ) ? $section : '_featured';
		$promotions      = array();
		$addons          = array();

		if ( '_featured' !== $current_section ) {
			$category       = $section ? $section : null;
			$term           = $search ? $search : null;
			$country        = WC()->countries->get_base_country();
			$extension_data = self::get_extension_data( $category, $term, $country );
			$addons         = $extension_data->products;
			$promotions     = ! empty( $extension_data->promotions ) ? $extension_data->promotions : array();
		}

		// We need Automattic\WooCommerce\Admin\RemoteInboxNotifications for the next part, if not remove all promotions.
		if ( ! WC()->is_wc_admin_active() ) {
			$promotions = array();
		}
		// Check for existence of promotions and evaluate out if we should show them.
		if ( ! empty( $promotions ) ) {
			foreach ( $promotions as $promo_id => $promotion ) {
				$evaluator = new PromotionRuleEngine\RuleEvaluator();
				$passed    = $evaluator->evaluate( $promotion->rules );
				if ( ! $passed ) {
					unset( $promotions[ $promo_id ] );
				}
			}
			// Transform promotions to the correct format ready for output.
			$promotions = self::format_promotions( $promotions );
		}

		/**
		 * Addon page view.
		 *
		 * @uses $addons
		 * @uses $search
		 * @uses $sections
		 * @uses $theme
		 * @uses $current_section
		 */
		include_once dirname( __FILE__ ) . '/views/html-admin-page-addons.php';
	}

	/**
	 * We're displaying page=wc-addons and page=wc-addons&section=helper as two separate pages.
	 * When we're on those pages, add body classes to distinguishe them.
	 *
	 * @param string $admin_body_class Unfiltered body class.
	 *
	 * @return string Body class with added class for Marketplace or My Subscriptions page.
	 */
	public static function filter_admin_body_classes( string $admin_body_class = '' ): string {
		if ( isset( $_GET['section'] ) && 'helper' === $_GET['section'] ) {
			return " $admin_body_class woocommerce-page-wc-subscriptions ";
		}

		return " $admin_body_class woocommerce-page-wc-marketplace ";
	}

	/**
	 * Take an action object and return the URL based on properties of the action.
	 *
	 * @param object $action Action object.
	 * @return string URL.
	 */
	public static function get_action_url( $action ): string {
		if ( ! isset( $action->url ) ) {
			return '';
		}

		if ( isset( $action->url_is_admin_query ) && $action->url_is_admin_query ) {
			return wc_admin_url( $action->url );
		}

		if ( isset( $action->url_is_admin_nonce_query ) && $action->url_is_admin_nonce_query ) {
			if ( empty( $action->nonce ) ) {
				return '';
			}
			return wp_nonce_url(
				admin_url( $action->url ),
				$action->nonce
			);
		}

		return $action->url;
	}

	/**
	 * Format the promotion data ready for display, ie fetch locales and actions.
	 *
	 * @param array $promotions Array of promotoin objects.
	 * @return array Array of formatted promotions ready for output.
	 */
	public static function format_promotions( array $promotions ): array {
		$formatted_promotions = array();
		foreach ( $promotions as $promotion ) {
			// Get the matching locale or fall back to en-US.
			$locale = PromotionRuleEngine\SpecRunner::get_locale( $promotion->locales );
			if ( null === $locale ) {
				continue;
			}

			$promotion_actions = array();
			if ( ! empty( $promotion->actions ) ) {
				foreach ( $promotion->actions as $action ) {
					$action_locale = PromotionRuleEngine\SpecRunner::get_action_locale( $action->locales );
					$url           = self::get_action_url( $action );

					$promotion_actions[] = array(
						'name'    => $action->name,
						'label'   => $action_locale->label,
						'url'     => $url,
						'primary' => isset( $action->is_primary ) ? $action->is_primary : false,
					);
				}
			}

			$formatted_promotions[] = array(
				'title'       => $locale->title,
				'description' => $locale->description,
				'image'       => ( 'http' === substr( $locale->image, 0, 4 ) ) ? $locale->image : WC()->plugin_url() . $locale->image,
				'image_alt'   => $locale->image_alt,
				'actions'     => $promotion_actions,
			);
		}
		return $formatted_promotions;
	}

	/**
	 * Map data from different endpoints to a universal format
	 *
	 * Search and featured products has a slightly different products' field names.
	 * Mapping converts different data structures into a universal one for further processing.
	 *
	 * @param mixed $data Product Card Data.
	 *
	 * @return object Converted data.
	 */
	public static function map_product_card_data( $data ) {
		$mapped = (object) null;

		$type = $data->type ?? null;

		// Icon.
		$mapped->icon = $data->icon ?? null;
		if ( null === $mapped->icon && 'banner' === $type ) {
			// For product-related banners icon is a product's image.
			$mapped->icon = $data->image ?? null;
		}
		// URL.
		$mapped->url = $data->link ?? null;
		if ( empty( $mapped->url ) ) {
			$mapped->url = $data->url ?? null;
		}
		// Title.
		$mapped->title = $data->title ?? null;
		// Vendor Name.
		$mapped->vendor_name = $data->vendor_name ?? null;
		if ( empty( $mapped->vendor_name ) ) {
			$mapped->vendor_name = $data->vendorName ?? null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}
		// Vendor URL.
		$mapped->vendor_url = $data->vendor_url ?? null;
		if ( empty( $mapped->vendor_url ) ) {
			$mapped->vendor_url = $data->vendorUrl ?? null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}
		// Description.
		$mapped->description = $data->excerpt ?? null;
		if ( empty( $mapped->description ) ) {
			$mapped->description = $data->description ?? null;
		}
		$has_currency = ! empty( $data->currency ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		// Is Free.
		if ( $has_currency ) {
			$mapped->is_free = 0 === $data->price;
		} else {
			$mapped->is_free = '&#36;0.00' === $data->price;
		}
		// Price.
		if ( $has_currency ) {
			$mapped->price = wc_price( $data->price, array( 'currency' => $data->currency ) );
		} else {
			$mapped->price = $data->price;
		}
		// Rating.
		$mapped->rating = $data->rating ?? null;
		if ( null === $mapped->rating ) {
			$mapped->rating = $data->averageRating ?? null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}
		// Reviews Count.
		$mapped->reviews_count = $data->reviews_count ?? null;
		if ( null === $mapped->reviews_count ) {
			$mapped->reviews_count = $data->reviewsCount ?? null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		return $mapped;
	}

	/**
	 * Render a product card
	 *
	 * There's difference in data structure (e.g. field names) between endpoints such as search and
	 * featured. Inner mapping helps to use universal field names for further work.
	 *
	 * @param mixed  $data       Product data.
	 * @param string $block_type Block type that's different from the default product card, e.g. a banner.
	 *
	 * @return void
	 */
	public static function render_product_card( $data, $block_type = null ) {
		$mapped      = self::map_product_card_data( $data );
		$product_url = self::add_in_app_purchase_url_params( $mapped->url );
		$class_names = array( 'product' );
		// Specify a class name according to $block_type (if it's specified).
		if ( null !== $block_type ) {
			$class_names[] = 'addons-product-' . $block_type;
		}

		$product_details_classes = 'product-details';
		if ( 'banner' === $block_type ) {
			$product_details_classes .= ' addon-product-banner-details';
		}
		?>
			<li class="<?php echo esc_attr( implode( ' ', $class_names ) ); ?>">
				<div class="<?php echo esc_attr( $product_details_classes ); ?>">
					<div class="product-text-container">
						<a href="<?php echo esc_url( $product_url ); ?>">
							<h2><?php echo esc_html( $mapped->title ); ?></h2>
						</a>
						<?php if ( ! empty( $mapped->vendor_name ) && ! empty( $mapped->vendor_url ) ) : ?>
							<div class="product-developed-by">
								<?php
									printf(
										/* translators: %s vendor link */
										esc_html__( 'Developed by %s', 'woocommerce' ),
										sprintf(
											'<a class="product-vendor-link" href="%1$s" target="_blank">%2$s</a>',
											esc_url_raw( $mapped->vendor_url ),
											wp_kses_post( $mapped->vendor_name )
										)
									);
								?>
							</div>
						<?php endif; ?>
						<p><?php echo wp_kses_post( $mapped->description ); ?></p>
					</div>
					<?php if ( ! empty( $mapped->icon ) ) : ?>
						<span class="product-img-wrap">
							<?php /* Show an icon if it exists */ ?>
							<img src="<?php echo esc_url( $mapped->icon ); ?>" />
						</span>
					<?php endif; ?>
				</div>
				<div class="product-footer">
					<div class="product-price-and-reviews-container">
						<div class="product-price-block">
							<?php if ( $mapped->is_free ) : ?>
								<span class="price"><?php esc_html_e( 'Free', 'woocommerce' ); ?></span>
							<?php else : ?>
								<span class="price"><?php echo wp_kses_post( $mapped->price ); ?></span>
								<span class="price-suffix"><?php esc_html_e( 'per year', 'woocommerce' ); ?></span>
							<?php endif; ?>
						</div>
						<?php if ( ! empty( $mapped->reviews_count ) && ! empty( $mapped->rating ) ) : ?>
							<?php /* Show rating and the number of reviews */ ?>
							<div class="product-reviews-block">
								<?php for ( $index = 1; $index <= 5; ++$index ) : ?>
									<?php $rating_star_class = 'product-rating-star product-rating-star__' . wccom_get_star_class( $mapped->rating, $index ); ?>
									<div class="<?php echo esc_attr( $rating_star_class ); ?>"></div>
								<?php endfor; ?>
								<span class="product-reviews-count">(<?php echo wp_kses_post( $mapped->reviews_count ); ?>)</span>
							</div>
						<?php endif; ?>
					</div>
					<a class="button" href="<?php echo esc_url( $product_url ); ?>">
						<?php esc_html_e( 'View details', 'woocommerce' ); ?>
					</a>
				</div>
			</li>
		<?php
	}
}

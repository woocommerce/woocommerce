<?php
/**
 * REST API Onboarding Tasks Controller
 *
 * Handles requests to complete various onboarding tasks.
 *
 * @package WooCommerce Admin/API
 */

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Admin\Features\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * Onboarding Tasks Controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Data_Controller
 */
class OnboardingTasks extends \WC_REST_Data_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'onboarding/tasks';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/import_sample_products',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_sample_products' ),
					'permission_callback' => array( $this, 'import_products_permission_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create_store_pages',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_store_pages' ),
					'permission_callback' => array( $this, 'create_pages_permission_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create_homepage',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_homepage' ),
					'permission_callback' => array( $this, 'create_pages_permission_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to create a product.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function import_products_permission_check( $request ) {
		if ( ! wc_rest_check_post_permissions( 'product', 'create' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to create a product.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_pages_permission_check( $request ) {
		if ( ! wc_rest_check_post_permissions( 'page', 'create' ) || ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create new pages.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Import sample products from WooCommerce sample CSV.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public static function import_sample_products() {
		include_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
		$file = WC_ABSPATH . 'sample-data/sample_products.csv';

		if ( file_exists( $file ) && class_exists( 'WC_Product_CSV_Importer' ) ) {
			// Override locale so we can return mappings from WooCommerce in English language stores.
			add_filter( 'locale', '__return_false', 9999 );
			$importer_class = apply_filters( 'woocommerce_product_csv_importer_class', 'WC_Product_CSV_Importer' );
			$args           = array(
				'parse'   => true,
				'mapping' => self::get_header_mappings( $file ),
			);
			$args           = apply_filters( 'woocommerce_product_csv_importer_args', $args, $importer_class );

			$importer = new $importer_class( $file, $args );
			$import   = $importer->import();
			return rest_ensure_response( $import );
		} else {
			return new \WP_Error( 'woocommerce_rest_import_error', __( 'Sorry, the sample products data file was not found.', 'woocommerce' ) );
		}
	}

	/**
	 * Get header mappings from CSV columns.
	 *
	 * @param string $file File path.
	 * @return array Mapped headers.
	 */
	public static function get_header_mappings( $file ) {
		include_once WC_ABSPATH . 'includes/admin/importers/mappings/mappings.php';

		$importer_class  = apply_filters( 'woocommerce_product_csv_importer_class', 'WC_Product_CSV_Importer' );
		$importer        = new $importer_class( $file, array() );
		$raw_headers     = $importer->get_raw_keys();
		$default_columns = wc_importer_default_english_mappings( array() );
		$special_columns = wc_importer_default_special_english_mappings( array() );

		$headers = array();
		foreach ( $raw_headers as $key => $field ) {
			$index             = $field;
			$headers[ $index ] = $field;

			if ( isset( $default_columns[ $field ] ) ) {
				$headers[ $index ] = $default_columns[ $field ];
			} else {
				foreach ( $special_columns as $regex => $special_key ) {
					if ( preg_match( self::sanitize_special_column_name_regex( $regex ), $field, $matches ) ) {
						$headers[ $index ] = $special_key . $matches[1];
						break;
					}
				}
			}
		}

		return $headers;
	}

	/**
	 * Sanitize special column name regex.
	 *
	 * @param  string $value Raw special column name.
	 * @return string
	 */
	public static function sanitize_special_column_name_regex( $value ) {
		return '/' . str_replace( array( '%d', '%s' ), '(.*)', trim( quotemeta( $value ) ) ) . '/';
	}

	/**
	 * Returns a valid cover block with an image, if one exists, or background as a fallback.
	 *
	 * @param  array $image Image to use for the cover block. Should contain a media ID and image URL.
	 * @return string Block content.
	 */
	private static function get_homepage_cover_block( $image ) {
		if ( ! empty( $image['url'] ) && ! empty( $image['id'] ) ) {
			return '<!-- wp:cover {"url":"' . esc_url( $image['url'] ) . '","id":' . intval( $image['id'] ) . ',"dimRatio":0} -->
			<div class="wp-block-cover" style="background-image:url(' . esc_url( $image['url'] ) . ')"><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"' . __( 'Write title…', 'woocommerce' ) . '","textColor":"white","fontSize":"large"} -->
			<p class="has-text-align-center has-large-font-size">' . __( 'Welcome to the store', 'woocommerce' ) . '</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"align":"center","textColor":"white"} -->
			<p class="has-text-color has-text-align-center">' . __( 'Write a short welcome message here', 'woocommerce' ) . '</p>
			<!-- /wp:paragraph -->

			<!-- wp:button {"align":"center"} -->
			<div class="wp-block-button aligncenter"><a class="wp-block-button__link">' . __( 'Go shopping', 'woocommerce' ) . '</a></div>
			<!-- /wp:button --></div></div>
			<!-- /wp:cover -->';
		}

		return '<!-- wp:cover {"dimRatio":0} -->
		<div class="wp-block-cover"><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"' . __( 'Write title…', 'woocommerce' ) . '","textColor":"white","fontSize":"large"} -->
		<p class="has-text-color has-text-align-center has-large-font-size">' . __( 'Welcome to the store', 'woocommerce' ) . '</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"align":"center","textColor":"white"} -->
		<p class="has-text-color has-text-align-center">' . __( 'Write a short welcome message here', 'woocommerce' ) . '</p>
		<!-- /wp:paragraph -->

		<!-- wp:button {"align":"center"} -->
		<div class="wp-block-button aligncenter"><a class="wp-block-button__link">' . __( 'Go shopping', 'woocommerce' ) . '</a></div>
		<!-- /wp:button --></div></div>
		<!-- /wp:cover -->';
	}

	/**
	 * Returns a valid media block with an image, if one exists, or a uninitialized media block the user can set.
	 *
	 * @param  array  $image Image to use for the cover block. Should contain a media ID and image URL.
	 * @param  string $align If the image should be aligned to the left or right.
	 * @return string Block content.
	 */
	private static function get_homepage_media_block( $image, $align = 'left' ) {
		$media_position = 'right' === $align ? '"mediaPosition":"right",' : '';
		$css_class      = 'right' === $align ? ' has-media-on-the-right' : '';

		if ( ! empty( $image['url'] ) && ! empty( $image['id'] ) ) {
			return '<!-- wp:media-text {' . $media_position . '"mediaId":' . intval( $image['id'] ) . ',"mediaType":"image"} -->
			<div class="wp-block-media-text alignwide' . $css_class . '""><figure class="wp-block-media-text__media"><img src="' . esc_url( $image['url'] ) . '" alt="" class="wp-image-' . intval( $image['id'] ) . '"/></figure><div class="wp-block-media-text__content"><!-- wp:paragraph {"placeholder":"' . __( 'Content…', 'woocommerce' ) . '","fontSize":"large"} -->
			<p class="has-large-font-size"></p>
			<!-- /wp:paragraph --></div></div>
			<!-- /wp:media-text -->';
		}

		return '<!-- wp:media-text {' . $media_position . '} -->
		<div class="wp-block-media-text alignwide' . $css_class . '"><figure class="wp-block-media-text__media"></figure><div class="wp-block-media-text__content"><!-- wp:paragraph {"placeholder":"' . __( 'Content…', 'woocommerce' ) . '","fontSize":"large"} -->
		<p class="has-large-font-size"></p>
		<!-- /wp:paragraph --></div></div>
		<!-- /wp:media-text -->';
	}

	/**
	 * Returns a homepage template to be inserted into a post. A different template will be used depending on the number of products.
	 *
	 * @param int $post_id ID of the homepage template.
	 * @return string Template contents.
	 */
	private static function get_homepage_template( $post_id ) {
		$products = wp_count_posts( 'product' );
		if ( $products->publish >= 4 ) {
			$images  = self::sideload_homepage_images( $post_id, 1 );
			$image_1 = ! empty( $images[0] ) ? $images[0] : '';
			$cover   = self::get_homepage_cover_block( $image_1 );

			return $cover . '
				<!-- wp:heading {"align":"center"} -->
				<h2 style="text-align:center">' . __( 'Shop by Category', 'woocommerce' ) . '</h2>
				<!-- /wp:heading -->
				<!-- wp:shortcode -->
				[product_categories limit="3" columns="3" orderby="menu_order"]
				<!-- /wp:shortcode -->
				<!-- wp:heading {"align":"center"} -->
				<h2 style="text-align:center">' . __( 'New In', 'woocommerce' ) . '</h2>
				<!-- /wp:heading -->
				<!-- wp:woocommerce/product-new {"columns":4} -->
				<div class="wp-block-woocommerce-product-new">[products limit="4" columns="4" orderby="date" order="DESC"]</div>
				<!-- /wp:woocommerce/product-new -->
				<!-- wp:heading {"align":"center"} -->
				<h2 style="text-align:center">' . __( 'Fan Favorites', 'woocommerce' ) . '</h2>
				<!-- /wp:heading -->
				<!-- wp:woocommerce/product-top-rated {"columns":4} -->
				<div class="wp-block-woocommerce-product-top-rated">[products limit="4" columns="4" orderby="rating"]</div>
				<!-- /wp:woocommerce/product-top-rated -->
				<!-- wp:heading {"align":"center"} -->
				<h2 style="text-align:center">' . __( 'On Sale', 'woocommerce' ) . '</h2>
				<!-- /wp:heading -->
				<!-- wp:woocommerce/product-on-sale {"columns":4} -->
				<div class="wp-block-woocommerce-product-on-sale">[products limit="4" columns="4" orderby="date" order="DESC" on_sale="1"]</div>
				<!-- /wp:woocommerce/product-on-sale -->
				<!-- wp:heading {"align":"center"} -->
				<h2 style="text-align:center">' . __( 'Best Sellers', 'woocommerce' ) . '</h2>
				<!-- /wp:heading -->
				<!-- wp:woocommerce/product-best-sellers {"columns":4} -->
				<div class="wp-block-woocommerce-product-best-sellers">[products limit="4" columns="4" best_selling="1"]</div>
				<!-- /wp:woocommerce/product-best-sellers -->
			';
		}

		$images  = self::sideload_homepage_images( $post_id, 3 );
		$image_1 = ! empty( $images[0] ) ? $images[0] : '';
		$image_2 = ! empty( $images[1] ) ? $images[1] : '';
		$image_3 = ! empty( $images[2] ) ? $images[2] : '';
		$cover   = self::get_homepage_cover_block( $image_1 );

		return $cover . '
		<!-- wp:heading {"align":"center"} -->
		<h2 style="text-align:center">' . __( 'New Products', 'woocommerce' ) . '</h2>
		<!-- /wp:heading -->

		<!-- wp:woocommerce/product-new /--> ' .

		self::get_homepage_media_block( $image_1, 'right' ) .
		self::get_homepage_media_block( $image_2, 'left' ) .
		self::get_homepage_media_block( $image_3, 'right' ) . '

		<!-- wp:woocommerce/featured-product /-->';
	}

	/**
	 * Gets the possible industry images from the plugin folder for sideloading. If an image doesn't exist, other.jpg is used a fallback.
	 *
	 * @return array An array of images by industry.
	 */
	private static function get_available_homepage_images() {
		$industry_images = array();
		$industries      = Onboarding::get_allowed_industries();
		foreach ( $industries as $industry_slug => $label ) {
			$industry_images[ $industry_slug ] = apply_filters( 'woocommerce_admin_onboarding_industry_image', plugins_url( 'images/onboarding/other-small.jpg', WC_ADMIN_PLUGIN_FILE ), $industry_slug );
		}
		return $industry_images;
	}

	/**
	 * Uploads a number of images to a homepage template, depending on the selected industry from the profile wizard.
	 *
	 * @param  int $post_id ID of the homepage template.
	 * @param  int $number_of_images The number of images that should be sideloaded (depending on how many media slots are in the template).
	 * @return array An array of images that have been attached to the post.
	 */
	private static function sideload_homepage_images( $post_id, $number_of_images ) {
		$profile            = get_option( Onboarding::PROFILE_DATA_OPTION, array() );
		$images_to_sideload = array();
		$available_images   = self::get_available_homepage_images();

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		if ( ! empty( $profile['industry'] ) ) {
			foreach ( $profile['industry'] as $selected_industry ) {
				if ( is_string( $selected_industry ) ) {
					$industry_slug = $selected_industry;
				} elseif ( is_array( $selected_industry ) && ! empty( $selected_industry['slug'] ) ) {
					$industry_slug = $selected_industry['slug'];
				} else {
					continue;
				}
				// Capture the first industry for use in our minimum images logic.
				$first_industry       = isset( $first_industry ) ? $first_industry : $industry_slug;
				$images_to_sideload[] = ! empty( $available_images[ $industry_slug ] ) ? $available_images[ $industry_slug ] : $available_images['other'];
			}
		}

		// Make sure we have at least {$number_of_images} images.
		if ( count( $images_to_sideload ) < $number_of_images ) {
			for ( $i = count( $images_to_sideload ); $i < $number_of_images; $i++ ) {
				// Fill up missing image slots with the first selected industry, or other.
				$industry             = isset( $first_industry ) ? $first_industry : 'other';
				$images_to_sideload[] = empty( $available_images[ $industry ] ) ? $available_images['other'] : $available_images[ $industry ];
			}
		}

		$already_sideloaded = array();
		$images_for_post    = array();
		foreach ( $images_to_sideload as $image ) {
			// Avoid uploading two of the same image, if an image is repeated.
			if ( ! empty( $already_sideloaded[ $image ] ) ) {
				$images_for_post[] = $already_sideloaded[ $image ];
				continue;
			}

			$sideload_id = \media_sideload_image( $image, $post_id, null, 'id' );
			if ( ! is_wp_error( $sideload_id ) ) {
				$sideload_url                 = wp_get_attachment_url( $sideload_id );
				$already_sideloaded[ $image ] = array(
					'id'  => $sideload_id,
					'url' => $sideload_url,
				);
				$images_for_post[]            = $already_sideloaded[ $image ];
			}
		}

		return $images_for_post;
	}

	/**
	 * Creates base store starter pages like my account and checkout.
	 * Note that WC_Install::create_pages already checks if pages exist before creating them again.
	 *
	 * @return bool
	 */
	public static function create_store_pages() {
		\WC_Install::create_pages();
		return true;
	}

	/**
	 * Create a homepage from a template.
	 *
	 * @return WP_Error|array
	 */
	public static function create_homepage() {
		$post_id = wp_insert_post(
			array(
				'post_title'   => __( 'Homepage', 'woocommerce' ),
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '', // Template content is updated below, so images can be attached to the post.
			)
		);

		if ( ! is_wp_error( $post_id ) ) {

			$template = self::get_homepage_template( $post_id );
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $template,
				)
			);

			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $post_id );
			update_option( 'woocommerce_onboarding_homepage_post_id', $post_id );

			return array(
				'status'         => 'success',
				'message'        => __( 'Homepage created.', 'woocommerce' ),
				'post_id'        => $post_id,
				'edit_post_link' => htmlspecialchars_decode( get_edit_post_link( $post_id ) ),
			);
		} else {
			return $post_id;
		}
	}
}

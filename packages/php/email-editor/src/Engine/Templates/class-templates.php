<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Templates;

use Automattic\WooCommerce\EmailEditor\Validator\Builder;
use WP_Block_Template;

/**
 * Templates class.
 */
class Templates {
	/**
	 * The plugin slug.
	 *
	 * @var string $plugin_slug
	 */
	private string $template_prefix = 'woocommerce';
	/**
	 * The post type.
	 *
	 * @var string[] $post_type
	 */
	private array $post_types = array();
	/**
	 * The template directory.
	 *
	 * @var string $template_directory
	 */
	private string $template_directory = __DIR__ . DIRECTORY_SEPARATOR;
	/**
	 * The templates registry.
	 *
	 * @var Templates_Registry $templates_registry
	 */
	private Templates_Registry $templates_registry;

	/**
	 * Constructor of the class.
	 *
	 * @param Templates_Registry $templates_registry The templates registry.
	 */
	public function __construct( Templates_Registry $templates_registry ) {
		$this->templates_registry = $templates_registry;
	}

	/**
	 * Initializes the class.
	 *
	 * @param string[] $post_types The list of post types registered for usage with email editor.
	 */
	public function initialize( array $post_types ): void {
		$this->post_types = $post_types;
		add_filter( 'theme_templates', array( $this, 'add_theme_templates' ), 10, 4 ); // Workaround needed when saving post – template association.
		add_filter( 'woocommerce_email_editor_register_templates', array( $this, 'register_templates' ) );
		$this->templates_registry->initialize();
		$this->register_post_types_to_api();
	}

	/**
	 * Get a block template by ID.
	 *
	 * @param string $template_slug The template slug.
	 * @return WP_Block_Template|null
	 */
	public function get_block_template( $template_slug ) {
		// Template id is always prefixed by active theme and get_stylesheet returns the active theme slug.
		$template_id = get_stylesheet() . '//' . $template_slug;
		return get_block_template( $template_id );
	}

	/**
	 * Register the templates via register_block_template
	 *
	 * @param Templates_Registry $templates_registry The templates registry.
	 */
	public function register_templates( Templates_Registry $templates_registry ): Templates_Registry {
		// Register basic blank template.
		$general_email_slug = 'email-general';
		$template_filename  = $general_email_slug . '.html';

		$general_email = new Template(
			$this->template_prefix,
			$general_email_slug,
			__( 'General Email', 'woocommerce' ),
			__( 'A general template for emails.', 'woocommerce' ),
			(string) file_get_contents( $this->template_directory . $template_filename ),
			$this->post_types
		);

		$templates_registry->register( $general_email );

		return $templates_registry;
	}

	/**
	 * Register post_types property to the templates rest api response.
	 *
	 * There is a PR that adds the property into the core https://github.com/WordPress/wordpress-develop/pull/7530
	 * Until it is merged, we need to add it manually.
	 */
	public function register_post_types_to_api(): void {
		$controller = new \WP_REST_Templates_Controller( 'wp_template' );
		$schema     = $controller->get_item_schema();
		// Future compatibility check if the post_types property is already registered.
		if ( isset( $schema['properties']['post_types'] ) ) {
			return;
		}
		register_rest_field(
			'wp_template',
			'post_types',
			array(
				'get_callback'    => array( $this, 'get_post_types' ),
				'update_callback' => null,
				'schema'          => Builder::string()->to_array(),
			)
		);
	}

	/**
	 * This is a callback function for adding post_types property to templates rest api response.
	 *
	 * @param array $response_object The rest API response object.
	 * @return array
	 */
	public function get_post_types( $response_object ): array {
		$template = $this->templates_registry->get_by_slug( $response_object['slug'] ?? '' );
		if ( $template ) {
			return $template->get_post_types();
		}

		return $response_object['post_types'] ?? array();
	}

	/**
	 * This is need to enable saving post – template association.
	 * When a theme doesn't support block_templates feature the association is not saved, because templates registered via register_block_template are not added to the list of available templates.
	 * https://github.com/WordPress/wordpress-develop/blob/cdc2f255acce57372b849d6278c4156e1056c749/src/wp-includes/class-wp-theme.php#L1355
	 *
	 * This function ensures that the email templates are in the list which is used for checking if the template can be saved in the association.
	 * See https://github.com/WordPress/wordpress-develop/blob/cdc2f255acce57372b849d6278c4156e1056c749/src/wp-includes/rest-api/endpoints/class-wp-rest-posts-controller.php#L1595-L1599
	 *
	 * @param array    $templates The templates.
	 * @param string   $theme The theme.
	 * @param \WP_Post $post The post.
	 * @param string   $post_type The post type.
	 * @return array
	 */
	public function add_theme_templates( $templates, $theme, $post, $post_type ) {
		if ( $post_type && ! in_array( $post_type, $this->post_types, true ) ) {
			return $templates;
		}
		$block_templates       = get_block_templates();
		$email_templates_slugs = array_map(
			function ( Template $template ) {
				return $template->get_slug();
			},
			$this->templates_registry->get_all()
		);
		foreach ( $block_templates as $block_template ) {
			if ( ! in_array( $block_template->slug, $email_templates_slugs, true ) ) {
				continue;
			}
			if ( isset( $templates[ $block_template->slug ] ) ) {
				continue;
			}
			$templates[ $block_template->slug ] = $block_template->title;  // Requires only the template title, not the full template object.
		}
		return $templates;
	}
}

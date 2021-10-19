<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use WP_Block;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\RestApi;

/**
 * AbstractBlock class.
 */
abstract class AbstractBlock {

	/**
	 * Block namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'woocommerce';

	/**
	 * Block name within this namespace.
	 *
	 * @var string
	 */
	protected $block_name = '';

	/**
	 * Tracks if assets have been enqueued.
	 *
	 * @var boolean
	 */
	protected $enqueued_assets = false;

	/**
	 * Instance of the asset API.
	 *
	 * @var AssetApi
	 */
	protected $asset_api;

	/**
	 * Instance of the asset data registry.
	 *
	 * @var AssetDataRegistry
	 */
	protected $asset_data_registry;

	/**
	 * Instance of the integration registry.
	 *
	 * @var IntegrationRegistry
	 */
	protected $integration_registry;

	/**
	 * Constructor.
	 *
	 * @param AssetApi            $asset_api Instance of the asset API.
	 * @param AssetDataRegistry   $asset_data_registry Instance of the asset data registry.
	 * @param IntegrationRegistry $integration_registry Instance of the integration registry.
	 * @param string              $block_name Optionally set block name during construct.
	 */
	public function __construct( AssetApi $asset_api, AssetDataRegistry $asset_data_registry, IntegrationRegistry $integration_registry, $block_name = '' ) {
		$this->asset_api            = $asset_api;
		$this->asset_data_registry  = $asset_data_registry;
		$this->integration_registry = $integration_registry;
		$this->block_name           = $block_name ? $block_name : $this->block_name;
		$this->initialize();
	}

	/**
	 * The default render_callback for all blocks. This will ensure assets are enqueued just in time, then render
	 * the block (if applicable).
	 *
	 * @param array|WP_Block $attributes Block attributes, or an instance of a WP_Block. Defaults to an empty array.
	 * @param string         $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public function render_callback( $attributes = [], $content = '' ) {
		$render_callback_attributes = $this->parse_render_callback_attributes( $attributes );
		if ( ! is_admin() ) {
			$this->enqueue_assets( $render_callback_attributes );
		}
		return $this->render( $render_callback_attributes, $content );
	}

	/**
	 * Enqueue assets used for rendering the block in editor context.
	 *
	 * This is needed if a block is not yet within the post content--`render` and `enqueue_assets` may not have ran.
	 */
	public function enqueue_editor_assets() {
		if ( $this->enqueued_assets ) {
			return;
		}
		$this->enqueue_data();
	}

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 */
	protected function initialize() {
		if ( empty( $this->block_name ) ) {
			_doing_it_wrong( __METHOD__, esc_html( __( 'Block name is required.', 'woocommerce' ) ), '4.5.0' );
			return false;
		}
		$this->integration_registry->initialize( $this->block_name . '_block' );
		$this->register_block_type_assets();
		$this->register_block_type();
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	/**
	 * Register script and style assets for the block type before it is registered.
	 *
	 * This registers the scripts; it does not enqueue them.
	 */
	protected function register_block_type_assets() {
		if ( null !== $this->get_block_type_editor_script() ) {
			$data     = $this->asset_api->get_script_data( $this->get_block_type_editor_script( 'path' ) );
			$has_i18n = in_array( 'wp-i18n', $data['dependencies'], true );

			$this->asset_api->register_script(
				$this->get_block_type_editor_script( 'handle' ),
				$this->get_block_type_editor_script( 'path' ),
				array_merge(
					$this->get_block_type_editor_script( 'dependencies' ),
					$this->integration_registry->get_all_registered_editor_script_handles()
				),
				$has_i18n
			);
		}
		if ( null !== $this->get_block_type_script() ) {
			$data     = $this->asset_api->get_script_data( $this->get_block_type_script( 'path' ) );
			$has_i18n = in_array( 'wp-i18n', $data['dependencies'], true );

			$this->asset_api->register_script(
				$this->get_block_type_script( 'handle' ),
				$this->get_block_type_script( 'path' ),
				array_merge(
					$this->get_block_type_script( 'dependencies' ),
					$this->integration_registry->get_all_registered_script_handles()
				),
				$has_i18n
			);
		}
	}

	/**
	 * Injects Chunk Translations into the page so translations work for lazy loaded components.
	 *
	 * The chunk names are defined when creating lazy loaded components using webpackChunkName.
	 *
	 * @param string[] $chunks Array of chunk names.
	 */
	protected function register_chunk_translations( $chunks ) {
		foreach ( $chunks as $chunk ) {
			$handle = 'wc-blocks-' . $chunk . '-chunk';
			$this->asset_api->register_script( $handle, $this->asset_api->get_block_asset_build_path( $chunk ), [], true );
			wp_add_inline_script(
				$this->get_block_type_script( 'handle' ),
				wp_scripts()->print_translations( $handle, false ),
				'before'
			);
			wp_deregister_script( $handle );
		}
	}

	/**
	 * Registers the block type with WordPress.
	 */
	protected function register_block_type() {
		register_block_type(
			$this->get_block_type(),
			array(
				'render_callback' => $this->get_block_type_render_callback(),
				'editor_script'   => $this->get_block_type_editor_script( 'handle' ),
				'editor_style'    => $this->get_block_type_editor_style(),
				'style'           => $this->get_block_type_style(),
				'attributes'      => $this->get_block_type_attributes(),
				'supports'        => $this->get_block_type_supports(),
			)
		);
	}

	/**
	 * Get the block type.
	 *
	 * @return string
	 */
	protected function get_block_type() {
		return $this->namespace . '/' . $this->block_name;
	}

	/**
	 * Get the render callback for this block type.
	 *
	 * Dynamic blocks should return a callback, for example, `return [ $this, 'render' ];`
	 *
	 * @see $this->register_block_type()
	 * @return callable|null;
	 */
	protected function get_block_type_render_callback() {
		return [ $this, 'render_callback' ];
	}

	/**
	 * Get the editor script data for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string
	 */
	protected function get_block_type_editor_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name ),
			'dependencies' => [ 'wc-blocks' ],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Get the editor style handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @return string|null
	 */
	protected function get_block_type_editor_style() {
		return 'wc-blocks-editor-style';
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string
	 */
	protected function get_block_type_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block-frontend',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name . '-frontend' ),
			'dependencies' => [],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @return string|null
	 */
	protected function get_block_type_style() {
		return 'wc-blocks-style';
	}

	/**
	 * Get the supports array for this block type.
	 *
	 * @see $this->register_block_type()
	 * @return string;
	 */
	protected function get_block_type_supports() {
		return [];
	}

	/**
	 * Get block attributes.
	 *
	 * @return array;
	 */
	protected function get_block_type_attributes() {
		return [];
	}

	/**
	 * Parses block attributes from the render_callback.
	 *
	 * @param array|WP_Block $attributes Block attributes, or an instance of a WP_Block. Defaults to an empty array.
	 * @return array
	 */
	protected function parse_render_callback_attributes( $attributes ) {
		return is_a( $attributes, 'WP_Block' ) ? $attributes->attributes : $attributes;
	}

	/**
	 * Render the block. Extended by children.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content ) {
		return $content;
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @internal This prevents the block script being enqueued on all pages. It is only enqueued as needed. Note that
	 * we intentionally do not pass 'script' to register_block_type.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 */
	protected function enqueue_assets( array $attributes ) {
		if ( $this->enqueued_assets ) {
			return;
		}
		$this->enqueue_data( $attributes );
		$this->enqueue_scripts( $attributes );
		$this->enqueued_assets = true;
	}

	/**
	 * Injects block attributes into the block.
	 *
	 * @param string $content HTML content to inject into.
	 * @param array  $attributes Key value pairs of attributes.
	 * @return string Rendered block with data attributes.
	 */
	protected function inject_html_data_attributes( $content, array $attributes ) {
		return preg_replace( '/<div /', '<div ' . $this->get_html_data_attributes( $attributes ) . ' ', $content, 1 );
	}

	/**
	 * Converts block attributes to HTML data attributes.
	 *
	 * @param array $attributes Key value pairs of attributes.
	 * @return string Rendered HTML attributes.
	 */
	protected function get_html_data_attributes( array $attributes ) {
		$data = [];

		foreach ( $attributes as $key => $value ) {
			if ( is_bool( $value ) ) {
				$value = $value ? 'true' : 'false';
			}
			if ( ! is_scalar( $value ) ) {
				$value = wp_json_encode( $value );
			}
			$data[] = 'data-' . esc_attr( strtolower( preg_replace( '/(?<!\ )[A-Z]/', '-$0', $key ) ) ) . '="' . esc_attr( $value ) . '"';
		}

		return implode( ' ', $data );
	}

	/**
	 * Data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		$registered_script_data = $this->integration_registry->get_all_registered_script_data();

		foreach ( $registered_script_data as $asset_data_key => $asset_data_value ) {
			if ( ! $this->asset_data_registry->exists( $asset_data_key ) ) {
				$this->asset_data_registry->add( $asset_data_key, $asset_data_value );
			}
		}

		if ( ! $this->asset_data_registry->exists( 'wcBlocksConfig' ) ) {
			$this->asset_data_registry->add(
				'wcBlocksConfig',
				[
					'buildPhase'    => Package::feature()->get_flag(),
					'pluginUrl'     => plugins_url( '/', dirname( __DIR__ ) ),
					'productCount'  => array_sum( (array) wp_count_posts( 'product' ) ),
					'restApiRoutes' => [
						'/wc/store' => array_keys( Package::container()->get( RestApi::class )->get_routes_from_namespace( 'wc/store' ) ),
					],
					'defaultAvatar' => get_avatar_url( 0, [ 'force_default' => true ] ),

					/*
					 * translators: If your word count is based on single characters (e.g. East Asian characters),
					 * enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
					 * Do not translate into your own language.
					 */
					'wordCountType' => _x( 'words', 'Word count type. Do not translate!', 'woocommerce' ),
				]
			);
		}
	}

	/**
	 * Register/enqueue scripts used for this block on the frontend, during render.
	 *
	 * @param array $attributes Any attributes that currently are available from the block.
	 */
	protected function enqueue_scripts( array $attributes = [] ) {
		if ( null !== $this->get_block_type_script() ) {
			wp_enqueue_script( $this->get_block_type_script( 'handle' ) );
		}
	}

	/**
	 * Script to append the correct sizing class to a block skeleton.
	 *
	 * @return string
	 */
	protected function get_skeleton_inline_script() {
		return "<script>
			var containers = document.querySelectorAll( 'div.wc-block-skeleton' );

			if ( containers.length ) {
				Array.prototype.forEach.call( containers, function( el, i ) {
					var w = el.offsetWidth;
					var classname = '';

					if ( w > 700 )
						classname = 'is-large';
					else if ( w > 520 )
						classname = 'is-medium';
					else if ( w > 400 )
						classname = 'is-small';
					else
						classname = 'is-mobile';

					if ( ! el.classList.contains( classname ) )  {
						el.classList.add( classname );
					}

					el.classList.remove( 'hidden' );
				} );
			}
		</script>";
	}
}

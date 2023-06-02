<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

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
	 * Block namespace.
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
	 * Constructor
	 *
	 * @param string $block_name Optional set block name during construct.
	 */
	public function __construct( $block_name = '' ) {
		if ( $block_name ) {
			$this->block_name = $block_name;
		}
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	/**
	 * Registers the block type with WordPress.
	 */
	public function register_block_type() {
		register_block_type(
			$this->namespace . '/' . $this->block_name,
			array(
				'editor_script' => 'wc-' . $this->block_name,
				'editor_style'  => 'wc-block-editor',
				'style'         => 'wc-block-style',
				'supports'      => [],
			)
		);
	}

	/**
	 * Append frontend scripts when rendering the block.
	 *
	 * @param array|\WP_Block $attributes Block attributes, or an instance of a WP_Block. Defaults to an empty array.
	 * @param string          $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public function render( $attributes = [], $content = '' ) {
		$this->enqueue_assets( is_a( $attributes, '\WP_Block' ) ? $attributes->attributes : $attributes );
		return $content;
	}

	/**
	 * Enqueue assets used for rendering the block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 */
	public function enqueue_assets( array $attributes = [] ) {
		if ( $this->enqueued_assets ) {
			return;
		}
		$this->enqueue_data( $attributes );
		$this->enqueue_scripts( $attributes );
		$this->enqueued_assets = true;
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
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		// noop. Child classes should override this if needed.
	}

	/**
	 * Register/enqueue scripts used for this block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 */
	protected function enqueue_scripts( array $attributes = [] ) {
		// noop. Child classes should override this if needed.
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

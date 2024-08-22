<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * AbstractInnerBlock class.
 */
abstract class AbstractInnerBlock extends AbstractBlock {

	/**
	 * Is this inner block lazy loaded? this helps us know if we should load its frontend script ot not.
	 *
	 * @var boolean
	 */
	protected $is_lazy_loaded = true;

	/**
	 * Registers the block type with WordPress.
	 *
	 * This method determines whether to use compiled metadata or file-based metadata
	 * for block registration.
	 */
    protected function register_block_type() {
		$this->load_compiled_block_metadata();

		if ( isset( parent::$compiled_block_metadata[ $this->namespace . '/' . $this->block_name ] ) ) {
			$this->register_block_type_from_compiled_metadata();
		} else {
			$this->register_block_type_from_file_metadata();
		}
	}

	/**
	 * Registers the block type using compiled metadata.
	 *
	 * This method is used when pre-compiled metadata is available for the block.
	 */
	protected function register_block_type_from_compiled_metadata() {
		$block_meta = self::$compiled_block_metadata[ $this->namespace . '/' . $this->block_name ];
		$transformed_meta = $this->transform_block_metadata( $block_meta );

		$block_settings = $this->get_block_settings();
		$merged_settings = array_merge( $transformed_meta, $block_settings );

		register_block_type_from_metadata( '', $merged_settings );
	}

	/**
	 * Registers the block type using metadata from a file.
	 *
	 * This method is used when pre-compiled metadata is not available and we need to read from block.json.
	 */
	protected function register_block_type_from_file_metadata() {
		$metadata_path = $this->asset_api->get_block_metadata_path( $this->block_name, 'inner-blocks/' );
		$block_settings = $this->get_block_settings();

		register_block_type_from_metadata( $metadata_path, $block_settings );
	}

	/**
	 * Get common block settings.
	 *
	 * @return array
	 */
	protected function get_block_settings() {
		$block_settings = [
			'render_callback' => $this->get_block_type_render_callback(),
			'editor_style'    => $this->get_block_type_editor_style(),
			'style'           => $this->get_block_type_style(),
		];

		if ( isset( $this->api_version ) && '2' === $this->api_version ) {
			$block_settings['api_version'] = 2;
		}

		return $block_settings;
	}

	/**
	 * For lazy loaded inner blocks, we don't want to enqueue the script but rather leave it for webpack to do that.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {

		if ( $this->is_lazy_loaded ) {
			return null;
		}

		return parent::get_block_type_script( $key );
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}
}

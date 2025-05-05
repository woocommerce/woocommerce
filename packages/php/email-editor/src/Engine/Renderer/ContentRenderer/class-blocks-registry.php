<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;

/**
 * Class Blocks_Registry
 */
class Blocks_Registry {
	/**
	 * Fallback renderer.
	 *
	 * @var ?Block_Renderer $fallback_renderer
	 */
	private $fallback_renderer = null;
	/**
	 * Array of block renderers.
	 *
	 * @var Block_Renderer[] $block_renderers_map
	 */
	private array $block_renderers_map = array();

	/**
	 * Adds block renderer to the registry.
	 *
	 * @param string         $block_name Block name.
	 * @param Block_Renderer $renderer Block renderer.
	 */
	public function add_block_renderer( string $block_name, Block_Renderer $renderer ): void {
		$this->block_renderers_map[ $block_name ] = $renderer;
	}

	/**
	 * Adds fallback renderer to the registry.
	 *
	 * @param Block_Renderer $renderer Fallback renderer.
	 */
	public function add_fallback_renderer( Block_Renderer $renderer ): void {
		$this->fallback_renderer = $renderer;
	}

	/**
	 * Checks if block renderer is registered.
	 *
	 * @param string $block_name Block name.
	 * @return bool
	 */
	public function has_block_renderer( string $block_name ): bool {
		return isset( $this->block_renderers_map[ $block_name ] );
	}

	/**
	 * Returns block renderer by block name.
	 *
	 * @param string $block_name Block name.
	 * @return Block_Renderer|null
	 */
	public function get_block_renderer( string $block_name ): ?Block_Renderer {
		return $this->block_renderers_map[ $block_name ] ?? null;
	}

	/**
	 * Returns fallback renderer.
	 *
	 * @return Block_Renderer|null
	 */
	public function get_fallback_renderer(): ?Block_Renderer {
		return $this->fallback_renderer;
	}

	/**
	 * Removes all block renderers from the registry.
	 */
	public function remove_all_block_renderers(): void {
		foreach ( array_keys( $this->block_renderers_map ) as $block_name ) {
			$this->remove_block_renderer( $block_name );
		}
	}

	/**
	 * Removes block renderer from the registry.
	 *
	 * @param string $block_name Block name.
	 */
	private function remove_block_renderer( string $block_name ): void {
		unset( $this->block_renderers_map[ $block_name ] );
	}
}

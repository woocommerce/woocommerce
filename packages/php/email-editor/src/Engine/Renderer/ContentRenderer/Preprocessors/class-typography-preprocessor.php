<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;

/**
 * This preprocessor is responsible for setting default typography values for blocks.
 */
class Typography_Preprocessor implements Preprocessor {
	/**
	 * List of styles that should be copied from parent to children.
	 *
	 * @var string[]
	 */
	private const TYPOGRAPHY_STYLES = array(
		'color',
		'font-size',
		'text-decoration',
	);

	/**
	 * Injected settings controller
	 *
	 * @var Settings_Controller
	 */
	private $settings_controller;

	/**
	 * Typography_Preprocessor constructor.
	 *
	 * @param Settings_Controller $settings_controller Settings controller.
	 */
	public function __construct(
		Settings_Controller $settings_controller
	) {
		$this->settings_controller = $settings_controller;
	}

	/**
	 * Method to preprocess the content before rendering
	 *
	 * @param array                                                                                                             $parsed_blocks Parsed blocks of the email.
	 * @param array{contentSize: string}                                                                                        $layout Layout of the email.
	 * @param array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles Styles of the email.
	 * @return array
	 */
	public function preprocess( array $parsed_blocks, array $layout, array $styles ): array {
		foreach ( $parsed_blocks as $key => $block ) {
			$block = $this->preprocess_parent( $block );
			// Set defaults from theme - this needs to be done on top level blocks only.
			$block = $this->set_defaults_from_theme( $block );

			$block['innerBlocks']  = $this->copy_typography_from_parent( $block['innerBlocks'], $block );
			$parsed_blocks[ $key ] = $block;
		}
		return $parsed_blocks;
	}

	/**
	 * Copy typography styles from parent to children
	 *
	 * @param array $children List of children blocks.
	 * @param array $parent_block  Parent block.
	 * @return array
	 */
	private function copy_typography_from_parent( array $children, array $parent_block ): array {
		foreach ( $children as $key => $child ) {
			$child                = $this->preprocess_parent( $child );
			$child['email_attrs'] = array_merge( $this->filterStyles( $parent_block['email_attrs'] ), $child['email_attrs'] );
			$child['innerBlocks'] = $this->copy_typography_from_parent( $child['innerBlocks'] ?? array(), $child );
			$children[ $key ]     = $child;
		}

		return $children;
	}

	/**
	 * Preprocess parent block
	 *
	 * @param array $block Block to preprocess.
	 * @return array
	 */
	private function preprocess_parent( array $block ): array {
		// Build styles that should be copied to children.
		$email_attrs = array();
		if ( isset( $block['attrs']['style']['color']['text'] ) ) {
			$email_attrs['color'] = $block['attrs']['style']['color']['text'];
		}
		// In case the fontSize is set via a slug (small, medium, large, etc.) we translate it to a number
		// The font size slug is set in $block['attrs']['fontSize'] and value in $block['attrs']['style']['typography']['fontSize'].
		if ( isset( $block['attrs']['fontSize'] ) ) {
			$block['attrs']['style']['typography']['fontSize'] = $this->settings_controller->translate_slug_to_font_size( $block['attrs']['fontSize'] );
		}
		// Pass font size to email_attrs.
		if ( isset( $block['attrs']['style']['typography']['fontSize'] ) ) {
			$email_attrs['font-size'] = $block['attrs']['style']['typography']['fontSize'];
		}
		if ( isset( $block['attrs']['style']['typography']['textDecoration'] ) ) {
			$email_attrs['text-decoration'] = $block['attrs']['style']['typography']['textDecoration'];
		}
		$block['email_attrs'] = array_merge( $email_attrs, $block['email_attrs'] ?? array() );
		return $block;
	}

	/**
	 * Filter styles to only include typography styles
	 *
	 * @param array $styles List of styles.
	 * @return array
	 */
	private function filterStyles( array $styles ): array {
		return array_intersect_key( $styles, array_flip( self::TYPOGRAPHY_STYLES ) );
	}

	/**
	 * Set default values from theme
	 *
	 * @param array $block Block to set defaults for.
	 * @return array
	 */
	private function set_defaults_from_theme( array $block ): array {
		$theme_data = $this->settings_controller->get_theme()->get_data();
		if ( ! ( $block['email_attrs']['color'] ?? '' ) ) {
			$block['email_attrs']['color'] = $theme_data['styles']['color']['text'] ?? null;
		}
		if ( ! ( $block['email_attrs']['font-size'] ?? '' ) ) {
			$block['email_attrs']['font-size'] = $theme_data['styles']['typography']['fontSize'];
		}
		return $block;
	}
}

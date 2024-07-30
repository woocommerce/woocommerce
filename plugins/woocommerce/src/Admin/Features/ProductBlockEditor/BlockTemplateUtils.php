<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

use Automattic\WooCommerce\LayoutTemplates\LayoutTemplateRegistry;

/**
 * Utils for block templates.
 */
class BlockTemplateUtils {
	/**
	 * Directory which contains all templates
	 *
	 * @var string
	 */
	const TEMPLATES_ROOT_DIR = 'templates';

	/**
	 * Directory names.
	 *
	 * @var array
	 */
	const DIRECTORY_NAMES = array(
		'TEMPLATES'      => 'product-form',
		'TEMPLATE_PARTS' => 'product-form/parts',
	);

	/**
	 * Gets the directory where templates of a specific template type can be found.
	 *
	 * @param string $template_type wp_template or wp_template_part.
	 * @return string
	 */
	private static function get_templates_directory( $template_type = 'wp_template' ) {
		$root_path                = dirname( __DIR__, 4 ) . '/' . self::TEMPLATES_ROOT_DIR . DIRECTORY_SEPARATOR;
		$templates_directory      = $root_path . self::DIRECTORY_NAMES['TEMPLATES'];
		$template_parts_directory = $root_path . self::DIRECTORY_NAMES['TEMPLATE_PARTS'];

		if ( 'wp_template_part' === $template_type ) {
			return $template_parts_directory;
		}

		return $templates_directory;
	}

	/**
	 * Return the path to a block template file.
	 * Otherwise, False.
	 *
	 * @param string $slug - Template slug.
	 * @return string|bool   Path to the template file or false.
	 */
	public static function get_block_template_path( $slug ) {
		$directory = self::get_templates_directory();
		$path      = trailingslashit( $directory ) . $slug . '.php';

		if ( ! file_exists( $path ) ) {
			return false;
		}

		return $path;
	}

	/**
	 * Get the template data from the headers.
	 *
	 * @param string $file_path - File path.
	 * @return array              Template data.
	 */
	public static function get_template_file_data( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return array();
		}

		$file_data = get_file_data(
			$file_path,
			array(
				'title'         => 'Title',
				'slug'          => 'Slug',
				'description'   => 'Description',
				'product_types' => 'Product Types',
			),
		);

		$file_data['product_types'] = explode( ',', trim( $file_data['product_types'] ) );

		return $file_data;
	}

	/**
	 * Get the template content from the file.
	 *
	 * @param string $file_path - File path.
	 * @return string Content.
	 */
	public static function get_template_content( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return '';
		}

		ob_start();
		include $file_path;
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
}

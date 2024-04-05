<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

use Automattic\WooCommerce\LayoutTemplates\LayoutTemplateRegistry;

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
    const DIRECTORY_NAMES = [
        'TEMPLATES' => 'product-form',
        'TEMPLATE_PARTS' => 'product-form/parts'
    ];

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
     * Get all the block templates from the directory by type.
     *
	 * @param string $template_type wp_template or wp_template_part.
     */
    public static function get_block_template( $file ) {
        $directory = self::get_templates_directory();

		return trailingslashit( $directory ) . $file;
    }

    /**
     * Get the template data from the headers.
     *
     * @param string $file_path File path.
     * @return array Template data.
     */
    public static function get_template_data( $file_path ) {
        $file_data = get_file_data(
            $file_path,
            array(
                'title'         => 'Title',
                'slug'          => 'Slug',
                'description'   => 'Description',
                'product_types' => 'Product Types'
            )
        );

        $file_data['product_types'] = explode( ',', trim( $file_data['product_types'] ) );

        return $file_data;
    }

}
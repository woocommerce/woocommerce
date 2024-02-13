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
        'TEMPLATES' => 'product-editor',
        'TEMPLATE_PARTS' => 'product-editor/parts'
    ];

    /**
     * Area
     *
     * @var string
     */
    const AREA = 'product-form';

	/**
	 * WooCommerce plugin slug
	 *
	 * @var string
	 */
	const PLUGIN_SLUG = 'woocommerce/woocommerce';

    /**
     * Get all the template paths in a given directory.
     *
     * @param string $directory Directory path.
     * @return array Array of file paths.
     */
    private function get_template_paths( $directory ) {
        return glob( $directory . '/*.html' );
    }

    /**
	 * Converts template paths into a slug
	 *
	 * @param string $path The template's path.
	 * @return string slug
	 */
	private function generate_template_slug_from_path( $path ) {
		$template_extension = '.html';

		return basename( $path, $template_extension );
	}

    /**
	 * Gets the directory where templates of a specific template type can be found.
	 *
	 * @param string $template_type wp_template or wp_template_part.
	 * @return string
	 */
    private function get_templates_directory( $template_type = 'wp_template' ) {
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
	 * @param array  $query Optional. Arguments to retrieve templates.
     */
    public function get_block_templates( $template_type, $query ) {
        $directory      = $this->get_templates_directory( $template_type );
		$template_files = $this->get_template_paths( $directory );

        $slugs          = $query['slug__in'] ?? [];
		$templates      = array();

		foreach ( $template_files as $template_file ) {
			$template_slug = $this->generate_template_slug_from_path( $template_file );

			// This template does not have a slug we're looking for. Skip it.
			if ( is_array( $slugs ) && count( $slugs ) > 0 && ! in_array( $template_slug, $slugs, true ) ) {
				continue;
			}

			$templates[] = $this->create_new_block_template( $template_file, $template_type, $template_slug );
		}

		return $templates;
    }

    /**
	 * Build a new template based on the filepath.
	 *
	 * @param string $template_file Block template file path.
	 * @param string $template_type wp_template or wp_template_part.
	 * @param string $template_slug Block template slug e.g. single-product.
	 * @return object Block template object.
	 */
	private function create_new_block_template( $template_file, $template_type, $template_slug ) {
        $template                 = new \WP_Block_Template();
        $template->id             = self::PLUGIN_SLUG . '//' . $template_slug;
        $template->theme          = self::PLUGIN_SLUG;
        $template->content        = file_get_contents( $template_file );
        $template->slug           = $template_slug;
        $template->source         = 'plugin';
        $template->area           = self::AREA;
        $template->type           = $template_type;
        $template->title          = $this->get_block_template_title( $template_slug );
        $template->description    = $this->get_block_template_description( $template_slug );
        $template->status         = 'publish';
        $template->has_theme_file = false;
        $template->is_custom      = false;
        $template->modified       = null;

        $before_block_visitor = '_inject_theme_attribute_in_template_part_block';
        $after_block_visitor  = null;
        $hooked_blocks        = get_hooked_blocks();
        if ( ! empty( $hooked_blocks ) || has_filter( 'hooked_block_types' ) ) {
            $before_block_visitor = make_before_block_visitor( $hooked_blocks, $template );
            $after_block_visitor  = make_after_block_visitor( $hooked_blocks, $template );
        }
        $blocks            = parse_blocks( $template->content );
        $template->content = $this->traverse_and_serialize_blocks( $blocks, $before_block_visitor, $after_block_visitor );

		return $template;
	}

    /**
     * Given an array of parsed block trees, applies callbacks before and after serializing them and
     * returns their concatenated output.
     *
     * Temporarily copied over to allow filtering block params.
     *
     * @since 6.4.0
     * @access private
     *
     * @see serialize_blocks()
     *
     * @param array[]  $blocks        An array of parsed blocks. See WP_Block_Parser_Block.
     * @param callable $pre_callback  Callback to run on each block in the tree before it is traversed and serialized.
     *                                It is called with the following arguments: &$block, $parent_block, $previous_block.
     *                                Its string return value will be prepended to the serialized block markup.
     * @param callable $post_callback Callback to run on each block in the tree after it is traversed and serialized.
     *                                It is called with the following arguments: &$block, $parent_block, $next_block.
     *                                Its string return value will be appended to the serialized block markup.
     * @return string Serialized block markup.
     */
    private function traverse_and_serialize_blocks( $blocks, $pre_callback = null, $post_callback = null ) {
        $result       = '';
        $parent_block = null; // At the top level, there is no parent block to pass to the callbacks; yet the callbacks expect a reference.

        foreach ( $blocks as $index => $block ) {
            if ( is_callable( $pre_callback ) ) {
                $prev = 0 === $index
                    ? null
                    : $blocks[ $index - 1 ];

                $result .= call_user_func_array(
                    $pre_callback,
                    array( &$block, &$parent_block, $prev )
                );
            }

            if ( is_callable( $post_callback ) ) {
                $next = count( $blocks ) - 1 === $index
                    ? null
                    : $blocks[ $index + 1 ];

                $post_markup = call_user_func_array(
                    $post_callback,
                    array( &$block, &$parent_block, $next )
                );
            }

            $result .= traverse_and_serialize_block( $block, $pre_callback, $post_callback );
            $result .= isset( $post_markup ) ? $post_markup : '';
        }

        return $result;
    }

    /**
     * Traverses a parsed block tree and applies callbacks before and after serializing it.
     *
     * Temporarily copied over to allow filtering block params.
     *
     * @since 6.4.0
     * @access private
     *
     * @see serialize_block()
     *
     * @param array    $block         A representative array of a single parsed block object. See WP_Block_Parser_Block.
     * @param callable $pre_callback  Callback to run on each block in the tree before it is traversed and serialized.
     *                                It is called with the following arguments: &$block, $parent_block, $previous_block.
     *                                Its string return value will be prepended to the serialized block markup.
     * @param callable $post_callback Callback to run on each block in the tree after it is traversed and serialized.
     *                                It is called with the following arguments: &$block, $parent_block, $next_block.
     *                                Its string return value will be appended to the serialized block markup.
     * @return string Serialized block markup.
     */
    private function traverse_and_serialize_block( $block, $pre_callback = null, $post_callback = null ) {
        $block_content = '';
        $block_index   = 0;

        $block = apply_filters( 'pre_traverse_and_serialize_block', $block );

        foreach ( $block['innerContent'] as $chunk ) {
            if ( is_string( $chunk ) ) {
                $block_content .= $chunk;
            } else {
                $inner_block = $block['innerBlocks'][ $block_index ];

                if ( is_callable( $pre_callback ) ) {
                    $prev = 0 === $block_index
                        ? null
                        : $block['innerBlocks'][ $block_index - 1 ];

                    $block_content .= call_user_func_array(
                        $pre_callback,
                        array( &$inner_block, &$block, $prev )
                    );
                }

                if ( is_callable( $post_callback ) ) {
                    $next = count( $block['innerBlocks'] ) - 1 === $block_index
                        ? null
                        : $block['innerBlocks'][ $block_index + 1 ];

                    $post_markup = call_user_func_array(
                        $post_callback,
                        array( &$inner_block, &$block, $next )
                    );
                }

                $block_content .= $this->traverse_and_serialize_block( $inner_block, $pre_callback, $post_callback );
                $block_content .= isset( $post_markup ) ? $post_markup : '';

                ++$block_index;
            }
        }

        if ( ! is_array( $block['attrs'] ) ) {
            $block['attrs'] = array();
        }

        return get_comment_delimited_block_content(
            $block['blockName'],
            $block['attrs'],
            $block_content
        );
    }

    /**
     * Get the block template title.
     *
     * @param string $template_slug Template slug.
     * @return string Template title.
     */
    private function get_block_template_title( $template_slug ) {
		$layout_template_registry = wc_get_container()->get( LayoutTemplateRegistry::class );
        $product_templates        = $layout_template_registry->instantiate_layout_templates();
        $product_template         = $product_templates[ $template_slug ] ?? null;

        if ( $product_template ) {
            return $product_template->get_title();
        }

        return ucwords( preg_replace( '/[\-_]/', ' ', $template_slug ) );
    }

    /**
     * Get the block template description.
     *
     * @param string $template_slug Template slug.
     * @return string Template description.
     */
    private function get_block_template_description( $template_slug ) {
		$layout_template_registry = wc_get_container()->get( LayoutTemplateRegistry::class );
        // @TODO The layout template registry might need a new name and a getter or a separate class altogether.
        $product_templates        = $layout_template_registry->instantiate_layout_templates();
        $product_template         = $product_templates[ $template_slug ] ?? null;

        if ( $product_template ) {
            return $product_template->get_description();
        }

        return '';
    }

    /**
     * Get additional data related to the template.
     */
    public function get_block_template_additional_data( $template_slug ) {
        $layout_template_registry = wc_get_container()->get( LayoutTemplateRegistry::class );
        $product_templates        = $layout_template_registry->instantiate_layout_templates();
        $product_template         = $product_templates[ $template_slug ] ?? null;
        
        if ( ! $product_template ) {
            return array();
        }

        return array(
            'product_types'        => $product_template->get_compatible_product_types(),
            'default_product_data' => $product_template->get_default_product_data()
        );
    }

    /**
	 * Add block product trait compatibility.
	 *
	 * @param array $block Parsed block.
	 * @return array Parsed block.
	 */
	public static function add_block_product_trait_compatibility( $block ) {
		if ( isset( $block['attrs']['metadata']['productTrait'] ) ) {
			$trait_slug = $block['attrs']['metadata']['productTrait'];
			$trait      = WC()->product_traits()->get_trait( $trait_slug );

			if ( $trait ) {
				$incompatible_traits = $trait::get_incompatible_traits();
				$block['attrs']['_templateBlockHideConditions'] = $block['attrs']['_templateBlockHideConditions'] ?? [];
				foreach ( $incompatible_traits as $incompatible_trait_slug ) {
                    $incompatible_trait = WC()->product_traits()->get_trait( $incompatible_trait_slug );
                    $property           = $incompatible_trait::get_product_property();
                    $value              = $incompatible_trait::get_product_property_enabled_value();
					$block['attrs']['_templateBlockHideConditions'][] = array(
						'expression' => "editedProduct.{$property} === '{$value}'",
					);
				}
			}

		}

		return $block;
	}
}
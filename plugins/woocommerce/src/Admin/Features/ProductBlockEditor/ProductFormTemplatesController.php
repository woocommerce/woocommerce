<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

class ProductFormTemplatesController {

    /**
     * Init
     */
    public function init() {
		add_filter( 'default_wp_template_part_areas', [ $this, 'register_product_form_template_part_area' ], 10, 1 );
        add_action( 'get_block_templates', [ $this, 'add_block_templates' ], 10, 3 );
        add_action( 'rest_api_init', [ $this, 'add_product_form_template_additional_data' ] );
    }

    /**
     * Add the additional data around templates to the REST response.
     */
    public function add_product_form_template_additional_data() {
        register_rest_field(
            'wp_template_part', 
            'additional_data',
            array(
                'get_callback'    => [ $this, 'get_additional_data' ], // custom function name 
                'update_callback' => null,
                'schema'          => null,
            )
        );
    }

    /**
     * Get additional data for the templates in the REST response.
     */
    public function get_additional_data( $object, $field_name, $request ) {
        if ( $object['area'] === BlockTemplateUtils::AREA ) {
            $block_template_utils = new BlockTemplateUtils();
            return $block_template_utils->get_block_template_additional_data( $object['slug'] );
        }

        return array();
    }

    /**
     * Add block templates to the templates endpoint.
     *
	 * @param array  $query_result Array of template objects.
	 * @param array  $query Optional. Arguments to retrieve templates.
	 * @param string $template_type wp_template or wp_template_part.
     * @return array
     */
    public function add_block_templates( $query_result, $query, $template_type = 'wp_template' ) {
        // @todo Uncomment this to prevent the template parts from showing in the site editor.
        // if ( ! isset( $query['area'] ) || $query['area'] !== self::AREA ) {
        //     return $query_result;
        // }

        $block_template_utils = new BlockTemplateUtils();
        $templates            = $block_template_utils->get_block_templates( $template_type, $query );
        
        foreach ( $templates as $template ) {
            if ( $this->should_include_template( $template, $query, $query_result ) ) {
                $query_result[] = $template;
            }
        }

        return $query_result;
    }

    /**
     * Check to see if a template should be included in the query results.
     *
     * @param \WP_Block_Template $template Block template instance.
	 * @param array              $query Optional. Arguments to retrieve templates.
     * @param array              $query_result Array of template objects.
     */
    public function should_include_template( $template, $query, $query_result ) {
        $theme_slug      = wp_get_theme()->get_stylesheet();
        $is_not_custom   = false === array_search(
            $theme_slug . '//' . $template->slug,
            array_column( $query_result, 'id' ),
            true
        );
        $fits_slug_query =
            ! isset( $query['slug__in'] ) || in_array( $template->slug, $query['slug__in'], true );
        $fits_area_query =
            ! isset( $query['area'] ) || ( isset( $template->area ) && $template->area === $query['area'] );

        return $is_not_custom && $fits_slug_query && $fits_area_query;
    }

    /**
	 * Add Mini-Cart to the default template part areas.
	 *
	 * @param array $default_area_definitions An array of supported area objects.
	 * @return array The supported template part areas including the Mini-Cart one.
	 */
	public function register_product_form_template_part_area( $default_area_definitions ) {
		$product_form_template_part_area = [
			'area'        => 'product-form',
			'label'       => __( 'Product Form', 'woocommerce' ),
			'description' => __( 'The form used to create products.', 'woocommerce' ),
			'icon'        => 'product',
			'area_tag'    => 'product-form',
		];
		return array_merge( $default_area_definitions, [ $product_form_template_part_area ] );
	}

}
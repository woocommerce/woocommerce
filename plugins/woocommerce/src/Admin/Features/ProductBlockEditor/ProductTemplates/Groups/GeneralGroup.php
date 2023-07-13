<?php
/**
 * Product Block Editor template "General" group
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\Groups;

trait GeneralGroup {

    /**
     * Add general group and fields.
     */
    protected function add_general_group() {
        $this->add_group(
            array(
                'id'    => self::GENERAL_GROUP,
                'title' => __( 'General', 'woocommerce' ),
                'order' => 10,
            )
        );
        $this->add_basic_details_section();
        $this->add_description_section();
        $this->add_images_section();
        $this->add_organization_section();
        $this->add_attributes_section();
    }

    /**
     * Add general group and fields.
     */
    protected function add_basic_details_section() {
        $this->add_section(
            array(
                'id'          => self::BASIC_DETAILS_SECTION,
                'title'       => __( 'Basic details', 'woocommerce' ),
                'description' => __( 'This info will be displayed on the product page, category pages, social media, and search results.', 'woocommerce' ),
                'parent'      => self::GENERAL_GROUP,
            )
        );
        $this->add_field(
            array(
                'parent'    => self::BASIC_DETAILS_SECTION,
                'blockName' => 'woocommerce/product-name-field',
                'attrs'     => array(
                    'name'  => 'Product name',
                ),
            ),
        );
        $this->add_field(
            array(
                'parent'    => self::BASIC_DETAILS_SECTION,
                'blockName' => 'woocommerce/product-summary-field',
            ),
        );
        $this->add_field(
            array(
                'id'        => 'pricing-columns',
                'parent'    => self::BASIC_DETAILS_SECTION,
                'blockName' => 'core/columns',
                'attrs'     => array(
                    'columns' => 2,
                ),
            ),
        );
        $this->add_field(
            array(
                'id'        => 'pricing-column-1',
                'parent'    => 'pricing-columns',
                'blockName' => 'core/column',
                'attrs'     => array(
                    'templateLock' => 'all',
                ),
            ),
        );
        $this->add_field(
            array(
                'blockName' => 'woocommerce/product-regular-price-field',
                'parent'    => 'pricing-column-1',
                'attrs'     => array(
                    'name'  => 'regular_price',
                    'label' => __( 'List price', 'woocommerce' ),
                    'help'  => __( 'Manage more settings in <PricingTab>Pricing.</PricingTab>', 'woocommerce' ),
                ),
            ),
        );
        $this->add_field(
            array(
                'id'        => 'pricing-column-2',
                'parent'    => 'pricing-columns',
                'blockName' => 'core/column',
                'attrs'     => array(
                    'templateLock' => 'all',
                ),
            ),
        );
        $this->add_field(
            array(
                'blockName' => 'woocommerce/product-sale-price-field',
                'parent'    => 'pricing-column-2',
                'attrs'     => array(
                    'label' => __( 'Sale price', 'woocommerce' ),
                ),
            ),
        );
    }

    protected function add_description_section() {
        $this->add_section(
            array(
                'id'          => self::DESCRIPTION_SECTION,
                'title'       => __( 'Description', 'woocommerce' ),
                'description' => __( 'What makes this product unique? What are its most important features? Enrich the product page by adding rich content using blocks.', 'woocommerce' ),
                'parent'      => self::GENERAL_GROUP,
            )
        );
        $this->add_field(
            array(
                'blockName' => 'woocommerce/product-description-field',
                'parent'    => self::DESCRIPTION_SECTION,
            ),
        );
    }

    protected function add_images_section() {
        $this->add_section(
            array(
                'id'          => self::IMAGES_SECTION,
                'title'       => __( 'Images', 'woocommerce' ),
                'description' => sprintf(
                    /* translators: %1$s: Images guide link opening tag. %2$s: Images guide link closing tag.*/
                    __( 'Drag images, upload new ones or select files from your library. For best results, use JPEG files that are 1000 by 1000 pixels or larger. %1$sHow to prepare images?%2$s', 'woocommerce' ),
                    '<a href="http://woocommerce.com/#" target="_blank" rel="noreferrer">',
                    '</a>'
                ),
                'parent'      => self::GENERAL_GROUP,
            )
        );
        $this->add_field(
            array(
                'blockName' => 'woocommerce/product-images-field',
                'parent'    => self::IMAGES_SECTION,
            ),
        );
    }

    protected function add_organization_section() {
        $this->add_section(
            array(
                'id'          => self::BASIC_DETAILS_SECTION . '/organization',
                'title'       => __( 'Organization & visibility', 'woocommerce' ),
                'description' => __( 'Help customers find this product by assigning it to categories or featuring it across your sales channels.', 'woocommerce' ),
                'parent'      => self::GENERAL_GROUP,
            )
        );
        $this->add_field(
            array(
                'blockName' => 'woocommerce/product-category-field',
                'parent'    => self::BASIC_DETAILS_SECTION . '/organization',
                'attrs'     => array(
                    'name' => 'categories',
                ),
            ),
        );
    }

    protected function add_attributes_section() {
        $this->add_section(
            array(
                'id'          => self::BASIC_DETAILS_SECTION . '/attributes',
                'title'       => __( 'Attributes', 'woocommerce' ),
                'description' => sprintf(
                    /* translators: %1$s: Attributes guide link opening tag. %2$s: Attributes guide link closing tag.*/
                    __( 'Add descriptive pieces of information that customers can use to filter and search for this product. %1$sLearn more%2$s', 'woocommerce' ),
                    '<a href="https://woocommerce.com/document/managing-product-taxonomies/#product-attributes" target="_blank" rel="noreferrer">',
                    '</a>'
                ),
                'parent'      => self::GENERAL_GROUP,
            )
        );
        $this->add_field(
            array(
                'blockName' => 'woocommerce/product-attributes-field',
                'parent'    => self::BASIC_DETAILS_SECTION . '/attributes',
            ),
        );
    }

}
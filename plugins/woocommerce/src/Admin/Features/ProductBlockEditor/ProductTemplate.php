<?php
/**
 * WooCommerce Product Block Editor
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

/**
 * The Product Template that represents the relation between the Product and
 * the LayoutTemplate (ProductFormTemplateInterface)
 *
 * @see ProductFormTemplateInterface
 */
class ProductTemplate {
	/**
	 * The template id.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The template title.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The post type.
	 *
	 * @var string
	 */
	private $post_type = 'product';

	/**
	 * The product data.
	 *
	 * @var array
	 */
	private $product_data = array();

	/**
	 * Alternate product datas.
	 *
	 * @var array
	 */
	private $alternate_product_datas = array();

	/**
	 * The template order.
	 *
	 * @var Integer
	 */
	private $order = 999;

	/**
	 * The layout template id.
	 *
	 * @var string
	 */
	private $layout_template_id = null;

	/**
	 * The template description.
	 *
	 * @var string
	 */
	private $description = null;

	/**
	 * The template icon.
	 *
	 * @var string
	 */
	private $icon = null;

	/**
	 * If the template is selectable by the user.
	 *
	 * @var bool
	 */
	private $is_selectable_by_user = true;

	/**
	 * ProductTemplate constructor
	 *
	 * @param array $data The data.
	 */
	public function __construct( array $data ) {
		$this->id    = $data['id'];
		$this->title = $data['title'];

		if ( isset( $data['product_data'] ) ) {
			$this->product_data = $data['product_data'];
		}

		if ( isset( $data['order'] ) ) {
			$this->order = $data['order'];
		}

		if ( isset( $data['layout_template_id'] ) ) {
			$this->layout_template_id = $data['layout_template_id'];
		}

		if ( isset( $data['description'] ) ) {
			$this->description = $data['description'];
		}

		if ( isset( $data['icon'] ) ) {
			$this->icon = $data['icon'];
		}

		if ( isset( $data['post_type'] ) ) {
			$this->post_type = $data['post_type'];
		}

		if ( isset( $data['alternate_product_datas'] ) ) {
			$this->alternate_product_datas = $data['alternate_product_datas'];
		}

		if ( isset( $data['is_selectable_by_user'] ) ) {
			$this->is_selectable_by_user = $data['is_selectable_by_user'];
		}
	}

	/**
	 * Get the template ID.
	 *
	 * @return string The ID.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the template title.
	 *
	 * @return string The title.
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Get the layout template ID.
	 *
	 * @return string The layout template ID.
	 */
	public function get_layout_template_id() {
		return $this->layout_template_id;
	}

	/**
	 * Set the layout template ID.
	 *
	 * @param string $layout_template_id The layout template ID.
	 */
	public function set_layout_template_id( string $layout_template_id ) {
		$this->layout_template_id = $layout_template_id;
	}

	/**
	 * Get the post type.
	 *
	 * @return string The post type.
	 */
	public function get_post_type() {
		return $this->post_type;
	}

	/**
	 * Get the product data.
	 *
	 * @return array The product data.
	 */
	public function get_product_data() {
		return $this->product_data;
	}

	/**
	 * Get the alternate product datas.
	 *
	 * @return array The alternate product datas.
	 */
	public function get_alternate_product_datas() {
		return $this->alternate_product_datas;
	}

	/**
	 * Get the template description.
	 *
	 * @return string The description.
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Set the template description.
	 *
	 * @param string $description The template description.
	 */
	public function set_description( string $description ) {
		$this->description = $description;
	}

	/**
	 * Get the template icon.
	 *
	 * @return string The icon.
	 */
	public function get_icon() {
		return $this->icon;
	}

	/**
	 * Set the template icon.
	 *
	 * @see https://github.com/WordPress/gutenberg/tree/trunk/packages/icons.
	 *
	 * @param string $icon The icon name from the @wordpress/components or a url for an external image resource.
	 */
	public function set_icon( string $icon ) {
		$this->icon = $icon;
	}

	/**
	 * Get the template order.
	 *
	 * @return int The order.
	 */
	public function get_order() {
		return $this->order;
	}

	/**
	 * Set the template order.
	 *
	 * @param int $order The template order.
	 */
	public function set_order( int $order ) {
		$this->order = $order;
	}

	/**
	 * Check if the template is selectable by the user.
	 *
	 * @return bool True if the template is selectable by the user.
	 */
	public function is_selectable_by_user() {
		return $this->is_selectable_by_user;
	}

	/**
	 * Get the product template as JSON like.
	 *
	 * @return array The JSON.
	 */
	public function to_json() {
		return array(
			'id'                    => $this->get_id(),
			'title'                 => $this->get_title(),
			'description'           => $this->get_description(),
			'icon'                  => $this->get_icon(),
			'order'                 => $this->get_order(),
			'layoutTemplateId'      => $this->get_layout_template_id(),
			'postType'              => $this->get_post_type(),
			'productData'           => $this->get_product_data(),
			'alternateProductDatas' => $this->get_alternate_product_datas(),
			'isSelectableByUser'    => $this->is_selectable_by_user(),
		);
	}

	/**
	 * Check if a product type is supported by the template.
	 *
	 * @param string $product_type The product type.
	 * @param string $post_type    The post type.
	 * @return bool True if the product type is supported.
	 */
	public function is_product_type_supported( ?string $product_type, string $post_type ) {
		if ( $this->post_type !== $post_type ) {
			return false;
		}

		if ( ( $this->product_data['type'] ?? null ) === ( $product_type ?? null ) ) {
			return true;
		}

		return count(
			array_filter(
				$this->get_alternate_product_datas(),
				function( $alternate_product_data ) use ( $product_type ) {
					return ( $alternate_product_data['type'] ?? null ) === ( $product_type ?? null );
				}
			)
		) > 0;
	}
}

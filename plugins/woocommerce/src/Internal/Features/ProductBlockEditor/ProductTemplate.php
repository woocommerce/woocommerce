<?php
/**
 * WooCommerce Product Block Editor
 */

namespace Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor;

/**
 * The Product Template that represents the relation between the Product and
 * the LayoutTemplate (ProductFormTemplateInterface)
 * 
 * @see ProductFormTemplateInterface
 */
class ProductTemplate {
	private $id;
	private $title;
	private $layout_template_id;
	private $product_data;
	private $description = null;
	private $order = 999;
	private $icon = null;
	
	/**
	 * ProductTemplate constructor
	 * 
	 * @param string $id The pattern ID.
	 * @param string $title The pattern title.
	 * @param string $layout_template_id The layout template id.
	 * @param array $product_data The product data.
	 */
	public function __construct( string $id, string $title, string $layout_template_id, array $product_data ) {
		$this->id = $id;
		$this->title = $title;
		$this->layout_template_id = $layout_template_id;
		$this->product_data = $product_data;
	}

	/**
	 * Get the pattern ID.
	 * 
	 * @return string The ID.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the pattern title.
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
	 * Get the product data.
	 * 
	 * @return array The product data.
	 */
	public function get_product_data() {
		return $this->product_data;
	}

	/**
	 * Get the pattern description.
	 * 
	 * @return string The description.
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Set the pattern description.
	 * 
	 * @param string $description The pattern description.
	 */
	public function set_description( string $description ) {
		$this->description = $description;
	}

	/**
	 * Get the pattern icon.
	 * 
	 * @return string The icon.
	 */
	public function get_icon() {
		return $this->icon;
	}

	/**
	 * Set the pattern icon.
	 * 
	 * @param string $icon The pattern icon.
	 */
	public function set_icon( string $icon ) {
		$this->icon = $icon;
	}

	/**
	 * Get the pattern order.
	 * 
	 * @return int The order.
	 */
	public function get_order() {
		return $this->order;
	}

	/**
	 * Set the pattern order.
	 * 
	 * @param int $order The pattern order.
	 */
	public function set_order( int $order ) {
		$this->order = $order;
	}

	/**
	 * Get the formatted pattern.
	 * 
	 * @return array The formatted pattern.
	 */
	function get_formatted() {
		return array(
			'id' => $this->get_id(),
			'title' => $this->get_title(),
			'description' => $this->get_description(),
			'icon' => $this->get_icon(),
			'order' => $this->get_order(),
			'layout_template_id' => $this->get_layout_template_id(),
			'product_data' => $this->get_product_data(),
		);
	}
}
<?php
/**
 * WooCommerce Product Block Editor
 */

namespace Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor;

/**
 * The Product Editor Pattern
 */
class ProductEditorPattern {
	private $id;
	private $title;
	private $template_id;
	private $product_data;
	private $description = null;
	private $icon = null;
	private $order = 999;
	
	/**
	 * ProductEditorPattern constructor
	 * 
	 * @param string $id The pattern ID.
	 * @param string $title The pattern title.
	 * @param string $template_id The template id.
	 * @param array $product_data The product data.
	 */
	public function __construct( string $id, string $title, string $template_id, array $product_data ) {
		$this->id = $id;
		$this->title = $title;
		$this->template_id = $template_id;
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
	 * Get the template ID.
	 * 
	 * @return string The template ID.
	 */
	public function get_template_id() {
		return $this->template_id;
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
			'template_id' => $this->get_template_id(),
			'product_data' => $this->get_product_data(),
		);
	}
}
<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Patterns;

/**
 * Register block patterns.
 */
class Patterns {
	/**
	 * Initialize block patterns.
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->register_block_pattern_categories();
	}

	/**
	 * Register block pattern category.
	 *
	 * @return void
	 */
	private function register_block_pattern_categories(): void {
		$categories = array(
			array(
				'name'        => 'email-contents',
				'label'       => _x( 'Email Contents', 'Block pattern category', 'woocommerce' ),
				'description' => __( 'A collection of email content layouts.', 'woocommerce' ),
			),
			array(
				'name'        => 'basic',
				'label'       => _x( 'Basic', 'Block pattern category', 'woocommerce' ),
				'description' => __( 'A collection of basic email layouts.', 'woocommerce' ),
			),
			array(
				'name'        => 'welcome',
				'label'       => _x( 'Welcome', 'Block pattern category', 'woocommerce' ),
				'description' => __( 'A collection of welcome email layouts.', 'woocommerce' ),
			),
			array(
				'name'        => 'abandoned-cart',
				'label'       => _x( 'Abandoned cart', 'Block pattern category', 'woocommerce' ),
				'description' => __( 'A collection of abandoned cart email layouts.', 'woocommerce' ),
			),
		);
		foreach ( $categories as $category ) {
			register_block_pattern_category(
				$category['name'],
				array(
					'label'       => $category['label'],
					'description' => $category['description'] ?? '',
				)
			);
		}
	}
}

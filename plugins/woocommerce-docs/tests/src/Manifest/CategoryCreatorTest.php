<?php

namespace WooCommerceDocs\Tests\Manifest;

use WP_UnitTestCase;
use WooCommerceDocs\Manifest\CategoryCreator;


/**
 * Class CategoryCreatorTest
 *
 * @package WooCommerceDocs\Tests\Manifest
 */
class CategoryCreatorTest extends WP_UnitTestCase {

	/**
	 * Test a category is created from a manifest entry.
	 */
	public function test_create_category() {
		$manifest_category = array(
			'category_title' => 'Test Category',
			'category_slug'  => 'test-category',
		);

		$term = CategoryCreator::create_or_update_category_from_manifest_entry( $manifest_category, null );
		$id   = $term['term_id'];

		$category = get_category( $id );

		$this->assertEquals( 'Test Category', $category->name );
		$this->assertEquals( 'test-category', $category->slug );
	}

	/**
	 * Test a category is updated from a manifest entry if it exists.
	 */
	public function test_update_category() {
		$manifest_category = array(
			'category_title' => 'Test Category',
			'category_slug'  => 'test-category',
		);

		$category_id = CategoryCreator::create_or_update_category_from_manifest_entry( $manifest_category, null );

		$manifest_category = array(
			'category_title' => 'Test Category 2',
			'category_slug'  => 'custom-slug',
		);

		$term = CategoryCreator::create_or_update_category_from_manifest_entry( $manifest_category, null );
		$id   = $term['term_id'];

		$category = get_category( $id );

		$this->assertEquals( 'Test Category 2', $category->name );
		$this->assertEquals( 'custom-slug', $category->slug );
	}

	/**
	 * Test a category can be created without a slug and it generates a
	 * default one from the title.
	 */
	public function test_create_category_no_slug() {
		$manifest_category = array(
			'category_title' => 'My Category',
		);

		$term     = CategoryCreator::create_or_update_category_from_manifest_entry( $manifest_category, null );
		$category = get_category( $term['term_id'] );

		$this->assertEquals( 'my-category', $category->slug );
	}

}

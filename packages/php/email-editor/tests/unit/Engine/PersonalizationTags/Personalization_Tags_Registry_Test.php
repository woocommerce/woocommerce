<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;

/**
 * Test cases for the Personalization_Tags_Registry class.
 */
class PersonalizationTagsRegistryTest extends TestCase {
	/**
	 * Property for the personalization tags registry.
	 *
	 * @var Personalization_Tags_Registry Personalization tags registry.
	 */
	private $registry;

	/**
	 * Set up the test case.
	 */
	protected function setUp(): void {
		$this->registry = new Personalization_Tags_Registry();
	}

	/**
	 * Register tag and retrieve it.
	 */
	public function testRegisterAndGetTag(): void {
		$callback = function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Callback parameters are required.
			return 'Personalized Value';
		};

		// Register a tag.
		$this->registry->register(
			new Personalization_Tag(
				'first_name_tag',
				'first_name',
				'Subscriber Info',
				$callback,
				array( 'description' => 'First name of the subscriber' )
			)
		);

		// Retrieve the tag.
		$tag = $this->registry->get_by_token( '[first_name]' );

		// Assert that the tag is registered correctly.
		$this->assertNotNull( $tag );
		$this->assertSame( 'first_name_tag', $tag->get_name() );
		$this->assertSame( '[first_name]', $tag->get_token() );
		$this->assertSame( 'Subscriber Info', $tag->get_category() );
		$this->assertSame( 'Personalized Value', $tag->execute_callback( array(), array() ) );
		$this->assertSame( array( 'description' => 'First name of the subscriber' ), $tag->get_attributes() );
		$this->assertSame( '[first_name description="First name of the subscriber"]', $tag->get_value_to_insert() );
	}

	/**
	 * Register tag and retrieve it.
	 */
	public function testRegisterAndGetTagWithBrackets(): void {
		$callback = function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Callback parameters are required.
			return 'Personalized Value';
		};

		// Register a tag.
		$this->registry->register(
			new Personalization_Tag(
				'Last Name',
				'[last_name]',
				'Subscriber Info',
				$callback,
				array( 'default' => 'subscriber' ),
				'[last_name default="user"]'
			)
		);

		// Retrieve the tag.
		$tag = $this->registry->get_by_token( '[last_name]' );

		// Assert that the tag is registered correctly.
		$this->assertNotNull( $tag );
		$this->assertSame( 'Last Name', $tag->get_name() );
		$this->assertSame( '[last_name]', $tag->get_token() );
		$this->assertSame( 'Subscriber Info', $tag->get_category() );
		$this->assertSame( 'Personalized Value', $tag->execute_callback( array(), array() ) );
		$this->assertSame( array( 'default' => 'subscriber' ), $tag->get_attributes() );
		$this->assertSame( '[last_name default="user"]', $tag->get_value_to_insert() );
	}

	/**
	 * Try to retrieve a tag that hasn't been registered.
	 */
	public function testRetrieveNonexistentTag(): void {
		$this->assertNull( $this->registry->get_by_token( 'nonexistent' ) );
	}

	/**
	 * Register multiple tags and retrieve them.
	 */
	public function testRegisterDuplicateTag(): void {
		$callback1 = function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Callback parameters are required.
			return 'Value 1';
		};

		$callback2 = function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Callback parameters are required.
			return 'Value 2';
		};

		// Register a tag.
		$this->registry->register( new Personalization_Tag( 'tag1', '[tag-1]', 'Category 1', $callback1 ) );

		// Attempt to register the same tag again.
		$this->registry->register( new Personalization_Tag( 'tag2', '[tag-2]', 'Category 2', $callback2 ) );

		// Retrieve the tag and ensure the first registration is preserved.
		/** @var Personalization_Tag $tag */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
		$tag = $this->registry->get_by_token( '[tag-1]' );
		$this->assertSame( 'tag1', $tag->get_name() );
		$this->assertSame( 'Category 1', $tag->get_category() );
		$this->assertSame( 'Value 1', $tag->execute_callback( array(), array() ) );
	}

	/**
	 * Retrieve all registered tags.
	 */
	public function testGetAllTags(): void {
		$callback = function () {
			return 'Value';
		};

		// Register multiple tags.
		$this->registry->register( new Personalization_Tag( 'tag1', '[tag-1]', 'Category 1', $callback ) );
		$this->registry->register( new Personalization_Tag( 'tag2', '[tag-2]', 'Category 2', $callback ) );

		// Retrieve all tags.
		$all_tags = $this->registry->get_all();

		// Assert the number of registered tags.
		$this->assertCount( 2, $all_tags );
		$this->assertArrayHasKey( '[tag-1]', $all_tags );
		$this->assertArrayHasKey( '[tag-2]', $all_tags );
	}

	/**
	 * Initialize the registry and apply a filter.
	 */
	public function testInitializeAppliesFilter(): void {
		// Mock WordPress's `apply_filters` function.
		global $wp_filter_applied;
		$wp_filter_applied = false;

		add_filter(
			'woocommerce_email_editor_register_personalization_tags',
			function ( $registry ) use ( &$wp_filter_applied ) {
				$wp_filter_applied = true;
				return $registry;
			}
		);

		// Initialize the registry.
		$this->registry->initialize();

		// Assert that the filter was applied.
		$this->assertTrue( $wp_filter_applied );
	}
}

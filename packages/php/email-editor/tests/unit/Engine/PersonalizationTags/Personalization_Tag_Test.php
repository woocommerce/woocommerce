<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the Personalization_Tag class.
 */
class Personalization_Tag_Test extends TestCase {
	/**
	 * Test that the tag can be created and used normally.
	 */
	public function testTagCreationAndUsage(): void {
		$callback = function () {
			return 'test value';
		};

		$tag = new Personalization_Tag(
			'Test Tag',
			'test_token',
			'Test Category',
			$callback,
			array( 'default' => 'fallback' ),
			'[test_token default="fallback"]'
		);

		$this->assertSame( 'Test Tag', $tag->get_name() );
		$this->assertSame( '[test_token]', $tag->get_token() );
		$this->assertSame( 'Test Category', $tag->get_category() );
		$this->assertSame( array( 'default' => 'fallback' ), $tag->get_attributes() );
		$this->assertSame( '[test_token default="fallback"]', $tag->get_value_to_insert() );
		$this->assertSame( 'test value', $tag->execute_callback( array(), array() ) );
	}

	/**
	 * Test that the callback can be retrieved.
	 */
	public function testGetCallback(): void {
		$callback = function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			return 'callback result: ' . ( $context['value'] ?? 'default' );
		};

		$tag = new Personalization_Tag(
			'Test Tag',
			'test_token',
			'Test Category',
			$callback
		);

		// Get the callback and verify it's the same one.
		$retrieved_callback = $tag->get_callback();
		$this->assertSame( $callback, $retrieved_callback );

		// Verify the callback can be executed.
		$result = call_user_func( $retrieved_callback, array( 'value' => 'test' ), array() );
		$this->assertSame( 'callback result: test', $result );
	}

	/**
	 * Test the value type defaults to html, accepts text, and falls back to html for unknown values.
	 */
	public function testValueType(): void {
		$callback = function () {
			return 'test value';
		};

		$tag = new Personalization_Tag( 'Test Tag', 'test_token', 'Test Category', $callback );
		$this->assertSame( Personalization_Tag::VALUE_TYPE_HTML, $tag->get_value_type() );

		$tag = new Personalization_Tag( 'Test Tag', 'test_token', 'Test Category', $callback, array(), null, array(), Personalization_Tag::VALUE_TYPE_TEXT );
		$this->assertSame( Personalization_Tag::VALUE_TYPE_TEXT, $tag->get_value_type() );

		$tag = new Personalization_Tag( 'Test Tag', 'test_token', 'Test Category', $callback, array(), null, array(), 'unknown-type' );
		$this->assertSame( Personalization_Tag::VALUE_TYPE_HTML, $tag->get_value_type() );
	}

	/**
	 * Test that deserialization is prevented for security reasons.
	 */
	public function testUnserializeThrowsException(): void {
		$callback = function () {
			return 'test value';
		};

		$tag = new Personalization_Tag(
			'Test Tag',
			'test_token',
			'Test Category',
			$callback
		);

		// Attempt to deserialize should throw an exception.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Deserialization of Personalization_Tag is not allowed for security reasons.' );

		$tag->__unserialize( array() );
	}
}

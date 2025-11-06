<?php
/**
 * Minimal PHPUnit stubs for Phan.
 *
 * This file provides basic stubs for PHPUnit to prevent Phan from complaining
 * about undefined classes and methods in test files.
 *
 * @package woocommerce/woocommerce
 */

namespace PHPUnit\Framework {
	/**
	 * TestCase stub.
	 */
	abstract class TestCase {
		/**
		 * Assert true.
		 *
		 * @param bool   $condition Condition.
		 * @param string $message Message.
		 */
		public static function assertTrue( $condition, string $message = '' ): void {}

		/**
		 * Assert false.
		 *
		 * @param bool   $condition Condition.
		 * @param string $message Message.
		 */
		public static function assertFalse( $condition, string $message = '' ): void {}

		/**
		 * Assert equals.
		 *
		 * @param mixed  $expected Expected.
		 * @param mixed  $actual Actual.
		 * @param string $message Message.
		 */
		public static function assertEquals( $expected, $actual, string $message = '' ): void {}

		/**
		 * Assert same.
		 *
		 * @param mixed  $expected Expected.
		 * @param mixed  $actual Actual.
		 * @param string $message Message.
		 */
		public static function assertSame( $expected, $actual, string $message = '' ): void {}

		/**
		 * Assert not equals.
		 *
		 * @param mixed  $expected Expected.
		 * @param mixed  $actual Actual.
		 * @param string $message Message.
		 */
		public static function assertNotEquals( $expected, $actual, string $message = '' ): void {}

		/**
		 * Assert count.
		 *
		 * @param int    $expectedCount Expected count.
		 * @param mixed  $haystack Haystack.
		 * @param string $message Message.
		 */
		public static function assertCount( int $expectedCount, $haystack, string $message = '' ): void {}

		/**
		 * Assert empty.
		 *
		 * @param mixed  $actual Actual.
		 * @param string $message Message.
		 */
		public static function assertEmpty( $actual, string $message = '' ): void {}

		/**
		 * Assert not empty.
		 *
		 * @param mixed  $actual Actual.
		 * @param string $message Message.
		 */
		public static function assertNotEmpty( $actual, string $message = '' ): void {}

		/**
		 * Assert null.
		 *
		 * @param mixed  $actual Actual.
		 * @param string $message Message.
		 */
		public static function assertNull( $actual, string $message = '' ): void {}

		/**
		 * Assert not null.
		 *
		 * @param mixed  $actual Actual.
		 * @param string $message Message.
		 */
		public static function assertNotNull( $actual, string $message = '' ): void {}

		/**
		 * Assert instance of.
		 *
		 * @param string $expected Expected class.
		 * @param mixed  $actual Actual.
		 * @param string $message Message.
		 */
		public static function assertInstanceOf( string $expected, $actual, string $message = '' ): void {}

		/**
		 * Expect exception.
		 *
		 * @param string $exception Exception class.
		 */
		public function expectException( string $exception ): void {}

		/**
		 * Expect exception message.
		 *
		 * @param string $message Message.
		 */
		public function expectExceptionMessage( string $message ): void {}

		/**
		 * Setup.
		 */
		protected function setUp(): void {}

		/**
		 * Teardown.
		 */
		protected function tearDown(): void {}
	}
}


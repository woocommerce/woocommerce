<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Logging;

use Automattic\WooCommerce\Internal\Admin\Logging\{ LogHandlerFileV2, PageController, Settings };
use WC_Unit_Test_Case;

/**
 * PageControllerTest class.
 */
class PageControllerTest extends WC_Unit_Test_Case {
	/**
	 * "System Under Test", an instance of the class to be tested.
	 *
	 * @var PageController
	 */
	private $sut;

	/**
	 * Instance of the file log handler, used to write real log entries.
	 *
	 * @var LogHandlerFileV2
	 */
	private $handler;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut     = wc_get_container()->get( PageController::class );
		$this->handler = new LogHandlerFileV2();
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$files = glob( Settings::get_log_directory() . '*.log' );
		foreach ( $files as $file ) {
			wp_delete_file( $file );
		}

		parent::tearDown();
	}

	/**
	 * Write a log entry with the file handler, then format the resulting log file line with the page controller.
	 *
	 * @param array $context The context values to log, excluding the source.
	 *
	 * @return string The formatted line.
	 */
	private function write_and_format_line( array $context ): string {
		$this->handler->handle(
			time(),
			'debug',
			'Test log entry.',
			array_merge( array( 'source' => 'test' ), $context )
		);

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 1, $paths );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$line = file_get_contents( reset( $paths ) );

		return $this->format_line( $line, 1 );
	}

	/**
	 * Invoke the private format_line method on the SUT, since its only caller reads
	 * query params through filter_input_array(), which is not settable from a test.
	 *
	 * @param string $line        The unformatted log file line.
	 * @param int    $line_number The line number.
	 *
	 * @return string
	 */
	private function format_line( string $line, int $line_number ): string {
		$method = new \ReflectionMethod( $this->sut, 'format_line' );
		$method->setAccessible( true );

		return $method->invoke( $this->sut, $line, $line_number );
	}

	/**
	 * Data provider for test_format_line_context_renders_as_valid_json.
	 *
	 * @return array
	 */
	public function provide_context_values(): array {
		return array(
			'namespaced class name' => array( array( 'class' => 'Automattic\\WooCommerce\\Internal\\Admin\\Logging\\LogHandlerFileV2' ) ),
			'windows path'          => array( array( 'path' => 'C:\\Windows\\System32' ) ),
			'double quotes'         => array( array( 'quote' => 'He said "hi" to "you"' ) ),
			'multibyte characters'  => array( array( 'text' => '中文字 café 🎉' ) ),
		);
	}

	/**
	 * @testdox A formatted log entry renders its context as a collapsible block whose content is valid JSON that decodes back to the original context values.
	 *
	 * @dataProvider provide_context_values
	 *
	 * @param array $context The context values to log, excluding the source.
	 */
	public function test_format_line_context_renders_as_valid_json( array $context ): void {
		$formatted = $this->write_and_format_line( $context );

		$this->assertStringContainsString( 'has-context', $formatted );
		$this->assertSame(
			1,
			preg_match( '|<details><summary>[^<]+</summary>(.+)</details>|s', $formatted, $matches ),
			'The formatted line should contain a collapsible context block'
		);

		// Decode entities the same way a browser does when displaying the markup.
		$json    = html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML401 );
		$decoded = json_decode( $json, true );

		$this->assertSame( $context, $decoded );
	}

	/**
	 * @testdox HTML in a log entry's context values is entity-encoded in the formatted output.
	 */
	public function test_format_line_escapes_html_in_context(): void {
		$formatted = $this->write_and_format_line( array( 'payload' => '<script>alert("xss")</script>' ) );

		$this->assertStringNotContainsString( '<script', $formatted );
		$this->assertStringContainsString( '&lt;script&gt;', $formatted );
	}

	/**
	 * @testdox A log entry whose context is not valid JSON renders as a plain, escaped line without a collapsible context block.
	 */
	public function test_format_line_malformed_context_renders_as_plain_line(): void {
		$line      = gmdate( 'Y-m-d\TH:i:sP' ) . ' DEBUG Test log entry. CONTEXT: {"unclosed":';
		$formatted = $this->format_line( $line, 1 );

		$this->assertStringNotContainsString( 'has-context', $formatted );
		$this->assertStringNotContainsString( '<details>', $formatted );
		$this->assertStringContainsString( 'CONTEXT: {&quot;unclosed&quot;:', $formatted );
	}
}

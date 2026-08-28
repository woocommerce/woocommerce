<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin;

use Automattic\WooCommerce\Admin\PluginsHelper;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for the PluginsHelper class.
 */
class PluginsHelperTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should join the error message and its string data into one reason.
	 */
	public function test_get_error_reason_uses_message_and_string_data(): void {
		$error = new WP_Error(
			'incompatible_php_required_version',
			'The package could not be installed.',
			'The PHP version on your server is 8.1.34, however the uploaded plugin requires 8.2.0.'
		);

		$reason = PluginsHelper::get_error_reason( $error );

		$this->assertSame(
			'The package could not be installed. The PHP version on your server is 8.1.34, however the uploaded plugin requires 8.2.0.',
			$reason,
			'Message and string data should be joined by a single space.'
		);
	}

	/**
	 * @testdox Should skip a message that is not a string instead of failing on it.
	 */
	public function test_get_error_reason_ignores_non_string_message(): void {
		// WP_Error::add() stores whatever a filter callback passed, so the message can be anything.
		$error = new WP_Error( 'custom', array( 'not', 'a', 'message' ), 'Detail sentence.' );

		$this->assertSame( 'Detail sentence.', PluginsHelper::get_error_reason( $error ) );
	}

	/**
	 * @testdox Should return only the message when the error data is not a string.
	 */
	public function test_get_error_reason_ignores_non_string_data(): void {
		$error = new WP_Error( 'fs_error', 'Could not create directory.', array( 'path' => '/tmp' ) );

		$this->assertSame(
			'Could not create directory.',
			PluginsHelper::get_error_reason( $error ),
			'Array error data must not be rendered.'
		);
	}

	/**
	 * @testdox Should skip non-error candidates and use the first WP_Error.
	 */
	public function test_get_error_reason_uses_first_wp_error_candidate(): void {
		$second = new WP_Error( 'download_failed', 'Download failed.' );

		$this->assertSame(
			'Download failed.',
			PluginsHelper::get_error_reason( false, null, $second ),
			'Non-error candidates (false, null) should be skipped.'
		);
	}

	/**
	 * @testdox Should return an empty string when no candidate is a WP_Error.
	 */
	public function test_get_error_reason_returns_empty_without_wp_error(): void {
		$this->assertSame( '', PluginsHelper::get_error_reason( false, null, true ) );
		$this->assertSame( '', PluginsHelper::get_error_reason() );
	}

	/**
	 * @testdox Should strip HTML and trim whitespace from the reason.
	 */
	public function test_get_error_reason_strips_html_and_trims(): void {
		$error = new WP_Error( 'x', ' <strong>Boom.</strong> ', '<a href="#">Details.</a>' );

		$this->assertSame( 'Boom. Details.', PluginsHelper::get_error_reason( $error ) );
	}

	/**
	 * @testdox Should return an empty string for a WP_Error with an empty message and no data.
	 */
	public function test_get_error_reason_returns_empty_for_empty_error(): void {
		$this->assertSame( '', PluginsHelper::get_error_reason( new WP_Error( 'x', '' ) ) );
	}

	/**
	 * @testdox Should keep a sentence boundary where a paragraph break was stripped.
	 */
	public function test_get_error_reason_separates_paragraphs(): void {
		// The shape validate_plugin_requirements() really returns.
		$error = new WP_Error(
			'plugin_php_incompatible',
			'<p><strong>Error:</strong> Current PHP version (8.1.34) does not meet minimum requirements for Foo. The plugin requires PHP 99.0.</p><p><a href="https://wordpress.org/support/update-php/">Learn more about updating PHP</a>.</p>'
		);

		$this->assertSame(
			'Error: Current PHP version (8.1.34) does not meet minimum requirements for Foo. The plugin requires PHP 99.0. Learn more about updating PHP.',
			PluginsHelper::get_error_reason( $error ),
			'A paragraph break must not run two sentences together.'
		);
	}

	/**
	 * @testdox Should not append raw plugin output captured by activate_plugin().
	 */
	public function test_get_error_reason_omits_opaque_error_data(): void {
		$error = new WP_Error(
			'unexpected_output',
			'The plugin generated unexpected output.',
			"Warning: include(): Failed opening 'x.php' in /var/www/plugin.php on line 12"
		);

		$this->assertSame(
			'The plugin generated unexpected output.',
			PluginsHelper::get_error_reason( $error ),
			'A raw output buffer is not a reason a merchant can act on and must not be surfaced.'
		);
	}

	/**
	 * @testdox Should cap a long reason so it stays safe to persist and transmit.
	 */
	public function test_get_error_reason_caps_long_reason(): void {
		$error = new WP_Error( 'fs_error', 'Could not create directory.', str_repeat( 'a', 1000 ) );

		$reason = PluginsHelper::get_error_reason( $error );

		$this->assertSame( 301, mb_strlen( $reason ), 'The reason should be capped at 300 characters plus an ellipsis.' );
		$this->assertStringEndsWith( "\u{2026}", $reason );
		$this->assertStringStartsWith( 'Could not create directory.', $reason );
	}

	/**
	 * @testdox Should report a not-installed plugin without repeating its slug.
	 */
	public function test_activate_plugins_error_does_not_contain_slug(): void {
		$result = PluginsHelper::activate_plugins( array( 'definitely-not-installed-plugin' ) );

		$this->assertIsArray( $result );
		$messages = $result['errors']->get_error_messages( 'definitely-not-installed-plugin' );

		$this->assertSame(
			array( 'The plugin is not installed yet.' ),
			$messages,
			'Activation errors should state only the reason; the client adds the plugin name.'
		);
	}

	/**
	 * @testdox Should ignore a requirement value that is not a version.
	 */
	public function test_get_requirements_error_reason_ignores_non_version_values(): void {
		$error = new WP_Error( 'incompatible_php_required_version', 'x' );

		// plugins_api_result is a public filter, so this value is not guaranteed to be a string.
		$this->assertSame(
			'',
			PluginsHelper::get_requirements_error_reason( $error, (object) array( 'requires_php' => array( '8.2' ) ) ),
			'An array requirement must not be coerced into the sentence.'
		);
		$this->assertSame(
			'',
			PluginsHelper::get_requirements_error_reason( $error, (object) array( 'requires_php' => true ) )
		);
	}

	/**
	 * @testdox Should strip markup and cap the length of a requirement reason.
	 */
	public function test_get_requirements_error_reason_is_normalized(): void {
		$error = new WP_Error( 'incompatible_php_required_version', 'x' );

		$this->assertSame(
			'It requires PHP 8.2 or newer, but this site runs PHP ' . PHP_VERSION . '.',
			PluginsHelper::get_requirements_error_reason( $error, (object) array( 'requires_php' => '<b>8.2</b>' ) ),
			'Markup reaching us through a filter must not survive into the reason.'
		);

		$long = PluginsHelper::get_requirements_error_reason( $error, (object) array( 'requires_php' => str_repeat( '9', 5000 ) ) );
		$this->assertSame( 301, mb_strlen( $long ), 'A requirement reason is capped like every other reason.' );
	}

	/**
	 * @testdox Should compose its own sentence for an unmet PHP requirement.
	 */
	public function test_get_requirements_error_reason_for_php(): void {
		$error = new WP_Error( 'incompatible_php_required_version', 'The package could not be installed.', 'core detail' );
		$api   = (object) array( 'requires_php' => '8.2.0' );

		$this->assertSame(
			sprintf( 'It requires PHP 8.2.0 or newer, but this site runs PHP %s.', PHP_VERSION ),
			PluginsHelper::get_requirements_error_reason( $error, $api )
		);
	}

	/**
	 * @testdox Should compose its own sentence for an unmet WordPress requirement.
	 */
	public function test_get_requirements_error_reason_for_wordpress(): void {
		$error = new WP_Error( 'incompatible_wp_required_version', 'The package could not be installed.', 'core detail' );
		$api   = (object) array( 'requires' => '9.9' );

		$this->assertSame(
			sprintf( 'It requires WordPress 9.9 or newer, but this site runs WordPress %s.', get_bloginfo( 'version' ) ),
			PluginsHelper::get_requirements_error_reason( $error, $api )
		);
	}

	/**
	 * @testdox Should return an empty string for other error codes, non-errors, or a missing requirement value.
	 */
	public function test_get_requirements_error_reason_returns_empty_otherwise(): void {
		$api = (object) array( 'requires_php' => '8.2.0' );

		$this->assertSame( '', PluginsHelper::get_requirements_error_reason( new WP_Error( 'download_failed', 'x' ), $api ) );
		$this->assertSame( '', PluginsHelper::get_requirements_error_reason( null, $api ) );
		$this->assertSame(
			'',
			PluginsHelper::get_requirements_error_reason( new WP_Error( 'incompatible_php_required_version', 'x' ), (object) array() ),
			'Without the required version from the API there is nothing accurate to say.'
		);
	}
}

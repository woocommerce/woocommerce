<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRegistry;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmails;

/**
 * Tests for the WCEmailTemplateSyncRegistry class.
 */
class WCEmailTemplateSyncRegistryTest extends \WC_Unit_Test_Case {
	/**
	 * Absolute path to the fixtures directory.
	 *
	 * @var string
	 */
	private string $fixtures_base;

	/**
	 * In-memory logger used to capture log calls across tests.
	 *
	 * @var object
	 */
	private $logger_spy;

	/**
	 * IDs injected into \WC_Emails::instance()->emails during the current test.
	 *
	 * @var string[]
	 */
	private array $injected_email_keys = array();

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		add_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );

		$this->fixtures_base = __DIR__ . '/fixtures/';

		$this->logger_spy = $this->create_capturing_logger();
		WCEmailTemplateSyncRegistry::set_logger( $this->logger_spy );

		WCEmailTemplateSyncRegistry::reset_cache();
	}

	/**
	 * Cleanup after each test.
	 */
	public function tearDown(): void {
		WCEmailTemplateSyncRegistry::reset_cache();
		WCEmailTemplateSyncRegistry::set_logger( null );

		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );

		$emails_container = \WC_Emails::instance();
		$reflection       = new \ReflectionClass( $emails_container );
		$property         = $reflection->getProperty( 'emails' );
		$property->setAccessible( true );
		$current = $property->getValue( $emails_container );
		foreach ( $this->injected_email_keys as $key ) {
			unset( $current[ $key ] );
		}
		$property->setValue( $emails_container, $current );
		$this->injected_email_keys = array();

		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );

		parent::tearDown();
	}

	/**
	 * Test that the registry exposes core emails with source=core and a parseable version.
	 *
	 * Every core email that is actually loaded into the WC_Emails registry during this test
	 * run must appear in the sync registry with `source = 'core'` and a non-empty `@version`.
	 * `customer_partially_refunded_order` is gated by a feature flag whose state we cannot
	 * reliably flip after WC_Emails has been initialised, so we only assert on emails that
	 * are actually registered in the current process.
	 */
	public function test_core_emails_populate_registry_with_core_source(): void {
		$registry                = WCEmailTemplateSyncRegistry::get_sync_enabled_emails();
		$registered_email_ids    = array_map(
			static fn ( \WC_Email $email ): string => (string) $email->id,
			array_values( \WC_Emails::instance()->get_emails() )
		);
		$expected_emails_in_test = array_intersect(
			WCTransactionalEmails::$core_transactional_emails,
			$registered_email_ids
		);

		$this->assertNotEmpty(
			$expected_emails_in_test,
			'Expected at least one core email to be registered with WC_Emails during the test.'
		);

		foreach ( $expected_emails_in_test as $email_id ) {
			$this->assertArrayHasKey(
				$email_id,
				$registry,
				sprintf( 'Expected core email "%s" to be in the sync registry.', $email_id )
			);
			$this->assertSame( 'core', $registry[ $email_id ]['source'] );
			$this->assertNotSame( '', $registry[ $email_id ]['version'] );
			$this->assertFileExists( $registry[ $email_id ]['template_path'] );
		}

		foreach ( $registry as $email_id => $entry ) {
			if ( 'core' === $entry['source'] ) {
				$this->assertContains(
					$email_id,
					$registered_email_ids,
					sprintf( 'Core-classified email "%s" must be registered with WC_Emails.', $email_id )
				);
			}
		}
	}

	/**
	 * Test that a third-party email added via the filter lands in the registry with source=third_party.
	 */
	public function test_third_party_email_with_version_header_is_registered(): void {
		$this->register_third_party_email(
			'third_party_with_version',
			'block/third-party-with-version.php',
			null
		);

		$registry = WCEmailTemplateSyncRegistry::get_sync_enabled_emails();

		$this->assertArrayHasKey( 'third_party_with_version', $registry );
		$this->assertSame( 'third_party', $registry['third_party_with_version']['source'] );
		$this->assertSame( '1.2.3', $registry['third_party_with_version']['version'] );
		$this->assertSame(
			$this->fixtures_base . 'block/third-party-with-version.php',
			$registry['third_party_with_version']['template_path']
		);
	}

	/**
	 * Test that a third-party email without a @version header is silently skipped and a warning is logged.
	 */
	public function test_third_party_email_without_version_is_skipped_and_warned(): void {
		$this->register_third_party_email(
			'third_party_no_version',
			'block/third-party-without-version.php',
			null
		);

		$registry = WCEmailTemplateSyncRegistry::get_sync_enabled_emails();

		$this->assertArrayNotHasKey( 'third_party_no_version', $registry );
		$this->assertLoggedLevelMatches(
			'warning',
			'/Email template sync skipped for email "third_party_no_version": missing @version header/'
		);
	}

	/**
	 * Test that an email with an unresolvable template path is skipped with a notice.
	 */
	public function test_unresolvable_template_path_is_skipped_with_notice(): void {
		$this->register_third_party_email(
			'third_party_missing_file',
			'block/does-not-exist.php',
			null
		);

		$registry = WCEmailTemplateSyncRegistry::get_sync_enabled_emails();

		$this->assertArrayNotHasKey( 'third_party_missing_file', $registry );
		$this->assertLoggedLevelMatches(
			'notice',
			'/Email template sync skipped for email "third_party_missing_file": template path not resolvable/'
		);
	}

	/**
	 * Test that an email ID registered via the filter without a matching WC_Email subclass is skipped.
	 */
	public function test_email_without_wc_email_subclass_is_skipped(): void {
		add_filter(
			'woocommerce_transactional_emails_for_block_editor',
			static function ( array $emails ): array {
				$emails[] = 'phantom_email_without_class';
				return $emails;
			}
		);

		$registry = WCEmailTemplateSyncRegistry::get_sync_enabled_emails();

		$this->assertArrayNotHasKey( 'phantom_email_without_class', $registry );
		$this->assertLoggedLevelMatches(
			'notice',
			'/Email template sync skipped for email "phantom_email_without_class": no WC_Email subclass registered/'
		);
	}

	/**
	 * Test that the registry resolves the block template via the plain -> block fallback
	 * for emails that set template_plain but not template_block.
	 */
	public function test_plain_to_block_template_name_fallback(): void {
		$this->register_third_party_email(
			'third_party_fallback',
			null,
			'plain/test-fallback.php'
		);

		$registry = WCEmailTemplateSyncRegistry::get_sync_enabled_emails();

		$this->assertArrayHasKey( 'third_party_fallback', $registry );
		$this->assertSame( '2.0.0', $registry['third_party_fallback']['version'] );
		$this->assertSame(
			$this->fixtures_base . 'block/test-fallback.php',
			$registry['third_party_fallback']['template_path']
		);
	}

	/**
	 * Test that the resolved registry is cached for the lifetime of the request.
	 */
	public function test_registry_is_cached_across_calls(): void {
		$first  = WCEmailTemplateSyncRegistry::get_sync_enabled_emails();
		$second = WCEmailTemplateSyncRegistry::get_sync_enabled_emails();

		$this->assertSame( $first, $second );

		$this->register_third_party_email(
			'third_party_with_version',
			'block/third-party-with-version.php',
			null
		);

		$third = WCEmailTemplateSyncRegistry::get_sync_enabled_emails();
		$this->assertArrayNotHasKey( 'third_party_with_version', $third, 'Second call should return cached data and not include the newly added email.' );
	}

	/**
	 * Test that reset_cache() forces re-resolution on the next call.
	 */
	public function test_reset_cache_forces_rebuild(): void {
		WCEmailTemplateSyncRegistry::get_sync_enabled_emails();

		$this->register_third_party_email(
			'third_party_with_version',
			'block/third-party-with-version.php',
			null
		);

		WCEmailTemplateSyncRegistry::reset_cache();

		$registry = WCEmailTemplateSyncRegistry::get_sync_enabled_emails();
		$this->assertArrayHasKey( 'third_party_with_version', $registry );
	}

	/**
	 * Test the get_email_sync_config() and is_enabled() helpers.
	 */
	public function test_convenience_helpers_return_expected_values(): void {
		$this->register_third_party_email(
			'third_party_with_version',
			'block/third-party-with-version.php',
			null
		);

		$this->assertTrue( WCEmailTemplateSyncRegistry::is_enabled( 'third_party_with_version' ) );
		$this->assertFalse( WCEmailTemplateSyncRegistry::is_enabled( 'unknown_email_id' ) );

		$config = WCEmailTemplateSyncRegistry::get_email_sync_config( 'third_party_with_version' );
		$this->assertIsArray( $config );
		$this->assertSame( '1.2.3', $config['version'] );
		$this->assertSame( 'third_party', $config['source'] );

		$this->assertNull( WCEmailTemplateSyncRegistry::get_email_sync_config( 'unknown_email_id' ) );
	}

	/**
	 * Test the extracted template-name resolver covers both branches.
	 */
	public function test_resolve_block_template_name_covers_both_branches(): void {
		$with_block                 = $this->createMock( \WC_Email::class );
		$with_block->template_block = 'emails/block/custom.php';
		$with_block->template_plain = 'emails/plain/ignored.php';

		$this->assertSame(
			'emails/block/custom.php',
			WCTransactionalEmailPostsGenerator::resolve_block_template_name( $with_block )
		);

		$plain_only                 = $this->createMock( \WC_Email::class );
		$plain_only->template_plain = 'emails/plain/customer-invoice.php';

		$this->assertSame(
			'emails/block/customer-invoice.php',
			WCTransactionalEmailPostsGenerator::resolve_block_template_name( $plain_only )
		);

		$empty                 = $this->createMock( \WC_Email::class );
		$empty->template_plain = '';

		$this->assertSame(
			'',
			WCTransactionalEmailPostsGenerator::resolve_block_template_name( $empty )
		);
	}

	/**
	 * Register a stub third-party WC_Email instance and hook the given ID into the
	 * block-editor filter so the registry considers it eligible.
	 *
	 * @param string      $email_id      Email ID to expose on the stub.
	 * @param string|null $template_block Block template name relative to the fixtures dir, or null.
	 * @param string|null $template_plain Plain template name relative to the fixtures dir, or null.
	 */
	private function register_third_party_email( string $email_id, ?string $template_block, ?string $template_plain ): void {
		$stub                 = $this->getMockBuilder( \WC_Email::class )
			->disableOriginalConstructor()
			->getMock();
		$stub->id             = $email_id;
		$stub->template_base  = $this->fixtures_base;
		$stub->template_block = $template_block;
		$stub->template_plain = $template_plain;

		$class_key = 'WC_Test_Email_' . $email_id;

		$emails_container = \WC_Emails::instance();
		$reflection       = new \ReflectionClass( $emails_container );
		$property         = $reflection->getProperty( 'emails' );
		$property->setAccessible( true );
		$current               = $property->getValue( $emails_container );
		$current[ $class_key ] = $stub;
		$property->setValue( $emails_container, $current );

		$this->injected_email_keys[] = $class_key;

		add_filter(
			'woocommerce_transactional_emails_for_block_editor',
			static function ( array $emails ) use ( $email_id ): array {
				if ( ! in_array( $email_id, $emails, true ) ) {
					$emails[] = $email_id;
				}
				return $emails;
			}
		);
	}

	/**
	 * Assert that at least one log call was recorded at the given level with a matching message.
	 *
	 * @param string $level    Log level (e.g. warning, notice).
	 * @param string $message_regex Regex pattern to match against the logged message.
	 */
	private function assertLoggedLevelMatches( string $level, string $message_regex ): void {
		$matches = array_filter(
			$this->logger_spy->records,
			static fn ( array $record ) => $record['level'] === $level && (bool) preg_match( $message_regex, $record['message'] )
		);

		$this->assertNotEmpty(
			$matches,
			sprintf(
				'Expected at least one "%s" log matching %s. Recorded: %s',
				$level,
				$message_regex,
				wp_json_encode( $this->logger_spy->records )
			)
		);
	}

	/**
	 * Create an in-memory logger that records every call for later assertions.
	 *
	 * @return object
	 */
	private function create_capturing_logger() {
		return new class() implements Email_Editor_Logger_Interface {
			/**
			 * Recorded log events.
			 *
			 * @var array<int, array{level: string, message: string, context: array}>
			 */
			public array $records = array();

			/**
			 * Emergency log.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 * @return void
			 */
			public function emergency( string $message, array $context = array() ): void {
				$this->log( 'emergency', $message, $context );
			}

			/**
			 * Alert log.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 * @return void
			 */
			public function alert( string $message, array $context = array() ): void {
				$this->log( 'alert', $message, $context );
			}

			/**
			 * Critical log.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 * @return void
			 */
			public function critical( string $message, array $context = array() ): void {
				$this->log( 'critical', $message, $context );
			}

			/**
			 * Error log.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 * @return void
			 */
			public function error( string $message, array $context = array() ): void {
				$this->log( 'error', $message, $context );
			}

			/**
			 * Warning log.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 * @return void
			 */
			public function warning( string $message, array $context = array() ): void {
				$this->log( 'warning', $message, $context );
			}

			/**
			 * Notice log.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 * @return void
			 */
			public function notice( string $message, array $context = array() ): void {
				$this->log( 'notice', $message, $context );
			}

			/**
			 * Info log.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 * @return void
			 */
			public function info( string $message, array $context = array() ): void {
				$this->log( 'info', $message, $context );
			}

			/**
			 * Debug log.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 * @return void
			 */
			public function debug( string $message, array $context = array() ): void {
				$this->log( 'debug', $message, $context );
			}

			/**
			 * Arbitrary-level log.
			 *
			 * @param string $level   Log level.
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 * @return void
			 */
			public function log( string $level, string $message, array $context = array() ): void {
				$this->records[] = array(
					'level'   => $level,
					'message' => $message,
					'context' => $context,
				);
			}
		};
	}
}

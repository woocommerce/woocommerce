<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\RemoteRules;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreConditions;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreOutputChecks;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the published agent readiness rules file.
 */
class RemoteRulesTest extends WC_Unit_Test_Case {

	private const URL = 'https://rules.example/agent-readiness.json';

	/**
	 * The System Under Test.
	 *
	 * @var RemoteRules
	 */
	private $sut;

	/**
	 * Requests made through the HTTP API.
	 *
	 * @var array<int, string>
	 */
	private array $requests = array();

	/**
	 * Body and status the next request answers with.
	 *
	 * @var array{body:string,code:int}
	 */
	private array $answer = array(
		'body' => '',
		'code' => 200,
	);

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		delete_option( RemoteRules::OPTION );
		$this->sut = new RemoteRules();
		add_filter( 'pre_http_request', array( $this, 'answer_request' ), 10, 3 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( RemoteRules::OPTION );
		parent::tearDown();
	}

	/**
	 * Record the request and answer with the prepared response.
	 *
	 * @param mixed  $pre  Short-circuit value.
	 * @param array  $args Request arguments.
	 * @param string $url  Request URL.
	 * @return array
	 */
	public function answer_request( $pre, $args, $url ) {
		$this->requests[] = $url;
		return array(
			'headers'  => array(),
			'body'     => $this->answer['body'],
			'response' => array(
				'code'    => $this->answer['code'],
				'message' => '',
			),
			'cookies'  => array(),
		);
	}

	/**
	 * Point the rules URL at the test URL and answer with this rules file.
	 *
	 * @param array $checks Channel maps by check id.
	 */
	private function serve( array $checks ): void {
		add_filter(
			'woocommerce_agent_readiness_rules_url',
			static function () {
				return self::URL;
			}
		);
		$file = array(
			'version' => '2026-11-01',
			'checks'  => array(),
		);
		foreach ( $checks as $id => $channels ) {
			$file['checks'][ $id ] = array(
				'label'    => 'Ignored by core',
				'channels' => $channels,
			);
		}
		$this->answer = array(
			'body' => (string) wp_json_encode( $file ),
			'code' => 200,
		);
	}

	/**
	 * A report service that reads the stored rules file.
	 */
	private function agent_readiness(): AgentReadiness {
		$store_conditions = new StoreConditions();
		$store_conditions->init( new ComingSoonHelper() );
		$output_checks = new StoreOutputChecks();
		$output_checks->init( new ComingSoonHelper() );
		$agent_readiness = new AgentReadiness();
		$agent_readiness->init( $store_conditions, $output_checks, $this->sut, wc_get_container()->get( \Automattic\WooCommerce\Internal\AgentReadiness\DeclaredToAgents::class ) );
		return $agent_readiness;
	}

	/**
	 * @testdox With no URL set nothing is fetched and the bundled rules apply.
	 */
	public function test_no_url_makes_no_request(): void {
		add_filter( 'woocommerce_agent_readiness_rules_url', '__return_empty_string' );
		$this->assertFalse( $this->sut->refresh() );
		$this->assertSame( array(), $this->requests );
		$this->assertSame( array(), $this->sut->channel_rules() );
		$this->assertArrayHasKey( 'missing_images', $this->agent_readiness()->get_channel_rules(), 'With no file, the bundled checks run' );
	}

	/**
	 * @testdox A check no channel asks for, such as image alt text, runs when the file names it with an empty channel map, and not when the file leaves it out.
	 */
	public function test_check_with_no_channels_follows_the_file(): void {
		$brand = array(
			'google' => array(
				'field'  => 'brand',
				'effect' => 'not_listable',
			),
		);
		$this->serve( array( 'missing_brand' => $brand ) );
		$this->assertTrue( $this->sut->refresh() );
		$this->assertArrayNotHasKey( 'image_alt_text', $this->agent_readiness()->get_channel_rules(), 'Left out of the file, it does not run' );

		$this->serve(
			array(
				'missing_brand'  => $brand,
				'image_alt_text' => new \stdClass(),
			)
		);
		$this->assertTrue( $this->sut->refresh() );
		$rules = $this->agent_readiness()->get_channel_rules();
		$this->assertSame( array(), $rules['image_alt_text'], 'Named with "channels": {}, it runs' );
		$this->assertArrayNotHasKey( 'missing_images', $rules, 'A channel check the file leaves out still does not run' );
	}

	/**
	 * @testdox A fetched rules file replaces a check's bundled channel map in the report.
	 */
	public function test_fetched_rules_change_the_report(): void {
		$this->serve(
			array(
				'missing_brand' => array(
					'google' => array(
						'field'  => 'brand',
						'effect' => 'not_listable',
					),
				),
			)
		);
		$this->assertTrue( $this->sut->refresh() );
		$this->assertSame( array( self::URL ), $this->requests );
		$this->assertSame( '2026-11-01', $this->sut->version() );

		WC_Helper_Product::create_simple_product();
		$report = $this->agent_readiness()->get_report();
		$checks = array_column( $report['checks'], null, 'id' );

		$this->assertSame( array( 'google' ), array_keys( $checks['missing_brand']['channels'] ), 'The file\'s map replaces the bundled one' );
		$this->assertSame( 1, $report['channels']['google']['not_listable'] );
		$this->assertArrayNotHasKey( 'missing_images', $checks, 'A check the file leaves out does not run' );
	}

	/**
	 * @testdox An effect_from date switches the effect from that day on, and with several dates the latest one already reached wins.
	 */
	public function test_effect_from_date(): void {
		$this->serve(
			array(
				'image_too_small' => array(
					'google' => array(
						'field'       => 'image_link',
						'effect'      => 'gaps',
						'effect_from' => array(
							'2027-06-01' => 'gaps',
							'2027-01-31' => 'not_listable',
						),
					),
				),
			)
		);
		$this->sut->refresh();

		$effect = fn( string $day ): string => $this->sut->channel_rules( strtotime( $day ) )['image_too_small']['google']['effect'];
		$this->assertSame( 'gaps', $effect( '2027-01-30' ) );
		$this->assertSame( 'not_listable', $effect( '2027-01-31' ) );
		$this->assertSame( 'not_listable', $effect( '2027-05-31' ) );
		$this->assertSame( 'gaps', $effect( '2027-06-01' ), 'Dates are applied in order, not file order' );
	}

	/**
	 * @testdox A check entry with a malformed channels map, channel or dated effect runs with its bundled channels, while the file's valid checks apply.
	 */
	public function test_malformed_check_entry_uses_bundled_channels(): void {
		$this->serve(
			array(
				'title_too_long'  => array(
					'google' => array(
						'field'  => 'title',
						'effect' => 'gaps',
					),
				),
				'missing_brand'   => array(
					'google' => array(
						'field'  => 'brand',
						'effect' => 'sometimes',
					),
				),
				'image_too_small' => array(
					'google'  => array(
						'field'       => 'image_link',
						'effect'      => 'gaps',
						'effect_from' => array( '2027-1-31' => 'not_listable' ),
					),
					'chatgpt' => array(
						'field'  => 'image_link',
						'effect' => 'gaps',
					),
				),
			)
		);
		$this->answer['body'] = (string) wp_json_encode(
			array_merge_recursive(
				json_decode( $this->answer['body'], true ),
				array(
					'checks' => array(
						'missing_gtin'   => array( 'channel' => array() ),
						'missing_images' => 'google',
					),
				)
			)
		);
		$this->assertTrue( $this->sut->refresh() );

		$agent_readiness = $this->agent_readiness();
		$defaults        = $agent_readiness->get_default_rules();
		$rules           = $agent_readiness->get_channel_rules();

		$this->assertSame( $defaults['missing_gtin']->channels(), $rules['missing_gtin'], 'A misspelled channels key runs the bundled channels' );
		$this->assertSame( $defaults['missing_images']->channels(), $rules['missing_images'], 'A non-object entry runs the bundled channels' );
		unset( $rules['missing_gtin'], $rules['missing_images'] );
		$ids = array_keys( $rules );
		sort( $ids );
		$this->assertSame( array( 'image_too_small', 'missing_brand', 'title_too_long' ), $ids, 'Only the file\'s checks run' );
		$this->assertSame( $defaults['missing_brand']->channels(), $rules['missing_brand'], 'A bad effect runs the bundled channels' );
		$this->assertSame( $defaults['image_too_small']->channels(), $rules['image_too_small'], 'A bad date runs the bundled channels' );
		$this->assertSame( array( 'google' ), array_keys( $rules['title_too_long'] ), 'The valid entry still applies' );
	}

	/**
	 * @testdox A failed fetch before any copy is stored is reported as a failure, and the bundled checks run.
	 */
	public function test_failed_fetch_with_no_copy_uses_bundled_checks(): void {
		add_filter(
			'woocommerce_agent_readiness_rules_url',
			static function () {
				return self::URL;
			}
		);
		$this->answer = array(
			'body' => 'Not found',
			'code' => 404,
		);

		$this->assertFalse( $this->sut->refresh(), 'The failed sync is visible to the caller' );
		$this->assertFalse( $this->sut->has_stored_file() );
		$this->assertArrayHasKey( 'missing_images', $this->agent_readiness()->get_channel_rules(), 'The bundled checks still run' );
	}

	/**
	 * @testdox A failed or malformed fetch keeps the last good copy.
	 */
	public function test_bad_fetch_keeps_last_copy(): void {
		$this->serve(
			array(
				'title_too_long' => array(
					'google' => array(
						'field'  => 'title',
						'effect' => 'not_listable',
					),
				),
			)
		);
		$this->sut->refresh();

		$this->answer = array(
			'body' => 'Not found',
			'code' => 404,
		);
		$this->assertFalse( $this->sut->refresh() );
		$this->answer = array(
			'body' => '{"version":"x"}',
			'code' => 200,
		);
		$this->assertFalse( $this->sut->refresh() );
		$this->answer = array(
			'body' => '<html><body>Maintenance</body></html>',
			'code' => 200,
		);
		$this->assertFalse( $this->sut->refresh() );

		$this->assertSame( 'not_listable', $this->sut->channel_rules()['title_too_long']['google']['effect'] );
	}

	/**
	 * @testdox With no URL filter the published rules file is fetched, and while it is not served the bundled checks run.
	 */
	public function test_default_url_is_the_published_file(): void {
		$this->answer = array(
			'body' => 'Not found',
			'code' => 404,
		);

		$this->assertFalse( $this->sut->refresh() );
		$this->assertSame( array( RemoteRules::PUBLISHED_URL ), $this->requests );
		$this->assertFalse( $this->sut->has_stored_file() );
		$this->assertArrayHasKey( 'missing_images', $this->agent_readiness()->get_channel_rules(), 'The bundled checks still run' );
	}

	/**
	 * @testdox A network error stores nothing, and the bundled checks run.
	 */
	public function test_network_error_uses_bundled_checks(): void {
		add_filter(
			'woocommerce_agent_readiness_rules_url',
			static function () {
				return self::URL;
			}
		);
		$fail = static function () {
			return new \WP_Error( 'http_request_failed', 'Operation timed out' );
		};
		add_filter( 'pre_http_request', $fail, 20 );

		$this->assertFalse( $this->sut->refresh() );
		remove_filter( 'pre_http_request', $fail, 20 );

		$this->assertSame( array(), $this->sut->channel_rules() );
		$this->assertArrayHasKey( 'missing_images', $this->agent_readiness()->get_channel_rules(), 'The bundled checks still run' );
	}

	/**
	 * @testdox Unknown checks and channels in the file never reach the rules filter, an entry with a bad effect runs its bundled channels, and the filter still has the last word.
	 */
	public function test_file_is_sanitized_and_filter_wins(): void {
		$this->serve(
			array(
				'not_a_check'    => array(
					'google' => array(
						'field'  => 'x',
						'effect' => 'not_listable',
					),
				),
				'title_too_long' => array(
					'google' => array(
						'field'  => 'title',
						'effect' => 'rejected',
					),
					'bing'   => array(
						'field'  => 'title',
						'effect' => 'gaps',
					),
				),
				'missing_gtin'   => array(
					'google' => array(
						'field'  => 'gtin',
						'effect' => 'not_listable',
					),
				),
			)
		);
		$this->sut->refresh();
		$seen = array();
		add_filter(
			'woocommerce_agent_readiness_rules',
			static function ( $rules ) use ( &$seen ) {
				$seen = $rules;

				$rules['missing_gtin']['google']['effect'] = 'gaps';
				return $rules;
			}
		);

		$rules = $this->agent_readiness()->get_channel_rules();

		$bundled = $this->agent_readiness()->get_default_rules()['title_too_long']->channels();
		$this->assertArrayNotHasKey( 'not_a_check', $seen );
		$this->assertSame( $bundled, $seen['title_too_long'], 'A malformed entry runs the bundled channels' );
		$this->assertSame( 'not_listable', $seen['missing_gtin']['google']['effect'], 'The filter sees the file\'s valid values' );
		$this->assertSame( $bundled, $rules['title_too_long'] );
		$this->assertSame( 'gaps', $rules['missing_gtin']['google']['effect'] );
	}

	/**
	 * @testdox A channel whose field is missing or not a string makes its check run with the bundled channels, so a malformed file does not warn.
	 */
	public function test_non_string_field_uses_bundled_channels(): void {
		$this->serve(
			array(
				'title_too_long' => array(
					'google'  => array(
						'field'  => array( 'title' ),
						'effect' => 'gaps',
					),
					'chatgpt' => array(
						'field'  => 'title',
						'effect' => 'gaps',
					),
				),
				'missing_gtin'   => array(
					'copilot' => array(
						'effect' => 'gaps',
					),
				),
				'missing_brand'  => array(
					'google' => array(
						'field'  => 'brand',
						'effect' => 'gaps',
					),
				),
			)
		);
		$this->assertTrue( $this->sut->refresh() );

		$agent_readiness = $this->agent_readiness();
		$defaults        = $agent_readiness->get_default_rules();
		$rules           = $agent_readiness->get_channel_rules();

		$this->assertSame( $defaults['title_too_long']->channels(), $rules['title_too_long'] );
		$this->assertSame( $defaults['missing_gtin']->channels(), $rules['missing_gtin'] );
	}

	/**
	 * @testdox A report after a new rules file uses the new rules, even from the same instance.
	 */
	public function test_report_reads_rules_fetched_after_an_earlier_report(): void {
		WC_Helper_Product::create_simple_product();
		$agent_readiness = $this->agent_readiness();
		$brand_channels  = static function ( array $report ): array {
			return array_column( $report['checks'], 'channels', 'id' )['missing_brand'];
		};
		$this->assertArrayHasKey( 'chatgpt', $brand_channels( $agent_readiness->get_report() ) );

		$this->serve(
			array(
				'missing_brand' => array(
					'google' => array(
						'field'  => 'brand',
						'effect' => 'not_listable',
					),
				),
			)
		);
		$this->sut->refresh();

		$this->assertSame( array( 'google' ), array_keys( $brand_channels( $agent_readiness->get_report() ) ) );
	}

	/**
	 * @testdox A file that names nothing this code can run does not turn the bundled checks off.
	 */
	public function test_file_with_no_known_check_keeps_bundled_checks(): void {
		$this->serve(
			array(
				'not_a_check' => array(
					'google' => array(
						'field'  => 'x',
						'effect' => 'not_listable',
					),
				),
			)
		);

		$this->assertTrue( $this->sut->refresh() );
		$this->assertArrayHasKey( 'missing_images', $this->agent_readiness()->get_channel_rules() );
	}

	/**
	 * @testdox A file with no shaped checks is a failed sync and does not replace the stored copy.
	 */
	public function test_file_with_no_shaped_checks_is_not_stored(): void {
		$this->serve(
			array(
				'title_too_long' => array(
					'google' => array(
						'field'  => 'title',
						'effect' => 'not_listable',
					),
				),
			)
		);
		$this->assertTrue( $this->sut->refresh() );

		$this->answer = array(
			'body' => '{"version":"x","checks":{"broken":"nope"}}',
			'code' => 200,
		);

		$this->assertFalse( $this->sut->refresh() );
		$this->assertSame( 'not_listable', $this->sut->channel_rules()['title_too_long']['google']['effect'] );
	}

	/**
	 * @testdox A file whose checks have no usable channel keeps the last good copy, so the active checks and their effects stay.
	 */
	public function test_file_with_no_usable_channel_keeps_last_copy(): void {
		$this->serve(
			array(
				'missing_brand' => array(
					'google' => array(
						'field'  => 'brand',
						'effect' => 'not_listable',
					),
				),
			)
		);
		$this->assertTrue( $this->sut->refresh() );
		$before = $this->agent_readiness()->get_channel_rules();
		$this->assertSame( array( 'missing_brand' ), array_keys( $before ), 'Only the file\'s check runs' );

		foreach (
			array(
				array(),
				array( 'google' => array() ),
				array(
					'google' => array(
						'field'  => 'brand',
						'effect' => 'sometimes',
					),
				),
				array(
					'not_a_channel' => array(
						'field'  => 'brand',
						'effect' => 'not_listable',
					),
				),
				array(
					'google' => array(
						'field'       => 'brand',
						'effect'      => 'not_listable',
						'effect_from' => array( '2020-01-01' => 'typo' ),
					),
				),
				array(
					'google' => array(
						'field'       => 'brand',
						'effect'      => 'not_listable',
						'effect_from' => array( 'soon' => 'gaps' ),
					),
				),
				array(
					'google' => array(
						'field'       => 'brand',
						'effect'      => 'not_listable',
						'effect_from' => 'gaps',
					),
				),
				array(
					'google' => array(
						'field'       => 'brand',
						'effect'      => 'not_listable',
						'effect_from' => null,
					),
				),
				array(
					'google'  => array(
						'field'  => 'brand',
						'effect' => 'sometimes',
					),
					'chatgpt' => array(
						'field'  => 'brand',
						'effect' => 'gaps',
					),
				),
			) as $channels
		) {
			$this->serve( array( 'missing_brand' => $channels ) );
			$this->assertFalse( $this->sut->refresh(), 'An unusable file is not stored: ' . wp_json_encode( $channels ) );
		}

		$this->assertSame( $before, $this->agent_readiness()->get_channel_rules() );
		$this->assertSame( 'not_listable', $before['missing_brand']['google']['effect'] );
	}

	/**
	 * @testdox Given the ids the code can run, a file naming only unknown checks keeps the last good copy.
	 */
	public function test_file_with_only_unknown_checks_keeps_last_copy(): void {
		$known = array_keys( $this->agent_readiness()->get_default_rules() );
		$this->serve(
			array(
				'title_too_long' => array(
					'google' => array(
						'field'  => 'title',
						'effect' => 'not_listable',
					),
				),
			)
		);
		$this->assertTrue( $this->sut->refresh( $known ) );

		$this->serve(
			array(
				'not_a_check' => array(
					'google' => array(
						'field'  => 'x',
						'effect' => 'not_listable',
					),
				),
			)
		);

		$this->assertFalse( $this->sut->refresh( $known ) );
		$this->assertSame( array( 'title_too_long' ), array_keys( $this->sut->channel_rules() ) );
	}

	/**
	 * @testdox The shipped rules file names the same checks the code can run, and those are the ones that run.
	 */
	public function test_shipped_rules_file_is_a_subset_of_the_code(): void {
		$path = dirname( __DIR__, 5 ) . '/src/Internal/AgentReadiness/rules.json';
		$file = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
		$this->assertIsArray( $file );
		$this->assertIsArray( $file['checks'] );

		$rules  = ( new AgentReadiness() )->get_default_rules();
		$known  = array_keys( $rules );
		$checks = array();
		foreach ( $file['checks'] as $id => $check ) {
			$this->assertContains( $id, $known, 'The file names a check the code does not have' );
			$checks[ $id ] = $check['channels'];
		}
		$expected = $known;
		$listed   = array_keys( $checks );
		sort( $expected );
		sort( $listed );
		$this->assertSame( $expected, $listed, 'A check in code that is missing from rules.json would not run once a store has fetched the file' );

		$this->serve( $checks );
		$this->sut->refresh();
		$running = array_keys( $this->agent_readiness()->get_channel_rules() );
		sort( $running );
		$this->assertSame( $expected, $running, 'Every check runs, including one no channel asks for' );
	}
}

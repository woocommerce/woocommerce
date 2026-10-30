<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\RemoteRules;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreConditions;
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
		remove_filter( 'pre_http_request', array( $this, 'answer_request' ), 10 );
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
		$agent_readiness = new AgentReadiness();
		$agent_readiness->init( $store_conditions, $this->sut );
		return $agent_readiness;
	}

	/**
	 * @testdox With no URL set nothing is fetched and the bundled rules apply.
	 */
	public function test_no_url_makes_no_request(): void {
		$this->assertFalse( $this->sut->refresh() );
		$this->assertSame( array(), $this->requests );
		$this->assertSame( array(), $this->sut->channel_rules() );
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
		$this->assertArrayHasKey( 'chatgpt', $checks['missing_images']['channels'], 'Checks the file leaves out keep their bundled map' );
	}

	/**
	 * @testdox An effect_from date switches the effect from that day on.
	 */
	public function test_effect_from_date(): void {
		$this->serve(
			array(
				'image_too_small' => array(
					'google' => array(
						'field'       => 'image_link',
						'effect'      => 'gaps',
						'effect_from' => array( '2027-01-31' => 'not_listable' ),
					),
				),
			)
		);
		$this->sut->refresh();

		$this->assertSame( 'gaps', $this->sut->channel_rules( strtotime( '2027-01-30' ) )['image_too_small']['google']['effect'] );
		$this->assertSame( 'not_listable', $this->sut->channel_rules( strtotime( '2027-01-31' ) )['image_too_small']['google']['effect'] );
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
	 * @testdox Unknown checks, channels and effects in the file never reach the rules filter, an entry with nothing valid keeps the bundled map, and the filter still has the last word.
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

		$this->assertArrayNotHasKey( 'not_a_check', $seen );
		$this->assertArrayNotHasKey( 'bing', $seen['title_too_long'] );
		$this->assertSame( 'not_listable', $seen['missing_gtin']['google']['effect'], 'The filter sees the file\'s valid values' );
		$this->assertSame( 'gaps', $rules['title_too_long']['google']['effect'], 'Nothing valid left: the bundled map stays' );
		$this->assertSame( 'gaps', $rules['missing_gtin']['google']['effect'] );
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
}

<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\AiAssistantExperiments;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessCheckPage;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\PluginUtil;
use WC_Unit_Test_Case;

/**
 * Tests for the Features for AI assistants feature and its Features page row.
 */
class AiAssistantExperimentsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var FeaturesController
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$plugin_util = $this->getMockBuilder( PluginUtil::class )->disableOriginalConstructor()->getMock();
		$plugin_util->method( 'get_woocommerce_aware_plugins' )->willReturn( array() );

		$this->sut = new FeaturesController();
		$this->sut->init( wc_get_container()->get( LegacyProxy::class ), $plugin_util );
	}

	/**
	 * @testdox The description lists every experiment in order and links the AI training choice.
	 */
	public function test_description_matches_the_approved_copy(): void {
		$link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=site-visibility' ) ) . '">';

		$this->assertSame(
			'Turn on newer ways for AI assistants to read your store: WebMCP on store forms, Markdown versions of pages, a full-content llms.txt, a Markdown sitemap and AI catalog, and AI training notices. Uses your ' . $link . 'AI training choice</a>.',
			AiAssistantExperiments::get_description()
		);
	}

	/**
	 * @testdox The AI training sentence is left out when AI training notices are not in the list.
	 */
	public function test_description_omits_ai_training_sentence_without_ai_training_notices(): void {
		$experiments = AiAssistantExperiments::get_experiments();
		unset( $experiments[ AiAssistantExperiments::AI_TRAINING_NOTICES ] );

		$description = AiAssistantExperiments::get_description( $experiments );

		$this->assertSame(
			'Turn on newer ways for AI assistants to read your store: WebMCP on store forms, Markdown versions of pages, a full-content llms.txt, and a Markdown sitemap and AI catalog.',
			$description
		);
	}

	/**
	 * @testdox The description joins two experiments without a comma.
	 */
	public function test_description_joins_two_experiments_with_and(): void {
		$description = AiAssistantExperiments::get_description(
			array(
				'first'  => 'First',
				'second' => 'Second',
			)
		);

		$this->assertSame( 'Turn on newer ways for AI assistants to read your store: First and Second.', $description );
	}

	/**
	 * @testdox The feature is experimental, off by default, and skips plugin compatibility checks.
	 */
	public function test_feature_definition(): void {
		$definition = $this->sut->get_feature_definition( AiAssistantExperiments::FEATURE_NAME );

		$this->assertNotNull( $definition, 'The feature should be registered.' );
		$this->assertSame( 'Features for AI assistants', $definition['name'] );
		$this->assertFalse( $definition['enabled_by_default'], 'The feature should be off by default.' );
		$this->assertTrue( $definition['is_experimental'], 'The feature should be experimental.' );
		$this->assertTrue( $this->sut->should_skip_compatibility_checks( AiAssistantExperiments::FEATURE_NAME ), 'Plugin compatibility checks should be skipped.' );
		$this->assertFalse( $this->sut->feature_is_enabled( AiAssistantExperiments::FEATURE_NAME ), 'The feature should start off.' );
	}

	/**
	 * @testdox The Features page shows the row only when agent readiness is on.
	 *
	 * @testWith ["yes", 1]
	 *           ["no", 0]
	 *
	 * @param string $agent_readiness Value of the agent readiness option.
	 * @param int    $expected_rows   Expected number of rows for the feature.
	 */
	public function test_row_is_shown_only_when_agent_readiness_is_on( string $agent_readiness, int $expected_rows ): void {
		update_option( 'woocommerce_feature_agent_readiness_enabled', $agent_readiness );
		$option_name = $this->sut->feature_enable_option_name( AiAssistantExperiments::FEATURE_NAME );

		$rows = array_values(
			array_filter(
				$this->sut->add_feature_settings( array(), 'features' ),
				static fn( $setting ) => ( $setting['id'] ?? null ) === $option_name
			)
		);

		$this->assertCount( $expected_rows, $rows );
		if ( $expected_rows ) {
			$this->assertSame( 'Features for AI assistants', $rows[0]['title'] );
			$this->assertSame( 'checkbox', $rows[0]['type'] );
			$this->assertSame( 'no', $rows[0]['default'], 'The row should default to off.' );
			$this->assertSame( AiAssistantExperiments::get_description(), $rows[0]['desc'] );
		}
	}

	/**
	 * @testdox The Features row names the experiments another plugin already provides, from the last check.
	 */
	public function test_provided_elsewhere_note_names_found_experiments(): void {
		$this->store_found(
			array(
				'llms-full'  => true,
				'tdmrep'     => true,
				'sitemap-md' => false,
				'agents-md'  => true,
			)
		);

		$this->assertSame(
			'Another plugin already provides a full-content llms.txt and AI training notices, so WooCommerce won’t add its own.',
			AiAssistantExperiments::get_provided_elsewhere_note()
		);
	}

	/**
	 * @testdox There's no note when none of the files are found, the check couldn't read them, or there's no check yet.
	 */
	public function test_provided_elsewhere_note_empty_without_found_files(): void {
		$this->store_found(
			array(
				'llms-full' => false,
				'ai-txt'    => null,
				'agents-md' => true,
			)
		);
		$this->assertSame( '', AiAssistantExperiments::get_provided_elsewhere_note() );

		delete_option( AgentReadiness::REPORT_OPTION );
		$this->assertSame( '', AiAssistantExperiments::get_provided_elsewhere_note() );
	}

	/**
	 * @testdox The note shows under the Features row only once the feature is on.
	 *
	 * @testWith ["yes", "Another plugin already provides a full-content llms.txt, so WooCommerce won’t add its own."]
	 *           ["no", ""]
	 *
	 * @param string $enabled  Value of the feature option.
	 * @param string $expected The row's line under its description.
	 */
	public function test_provided_elsewhere_note_only_when_on( string $enabled, string $expected ): void {
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		update_option( 'woocommerce_feature_ai_assistant_experiments_enabled', $enabled );
		$this->store_found( array( 'llms-full' => true ) );
		$option_name = $this->sut->feature_enable_option_name( AiAssistantExperiments::FEATURE_NAME );

		$rows = array_values(
			array_filter(
				$this->sut->add_feature_settings( array(), 'features' ),
				static fn( $setting ) => ( $setting['id'] ?? null ) === $option_name
			)
		);

		$this->assertSame( $expected, $rows[0]['desc_tip'] );
	}

	/**
	 * @testdox While the feature is off, AI shopping health suggests it and links to its row on the Features page.
	 */
	public function test_health_notice_copy(): void {
		update_option( 'woocommerce_feature_ai_assistant_experiments_enabled', 'no' );

		$this->assertSame(
			array(
				'text' => 'Try newer standards for AI assistants, such as WebMCP and Markdown versions of your pages.',
				'link' => array(
					'href' => admin_url( 'admin.php?page=wc-settings&tab=advanced&section=features#woocommerce_feature_ai_assistant_experiments_enabled' ),
					'text' => 'Turn on in Features',
				),
			),
			$this->view_with_report()['experiments']
		);
	}

	/**
	 * @testdox The notice goes away once the feature is on.
	 */
	public function test_health_notice_hidden_when_on(): void {
		update_option( 'woocommerce_feature_ai_assistant_experiments_enabled', 'yes' );

		$this->assertNull( $this->view_with_report()['experiments'] );
	}

	/**
	 * @testdox The notice stays away for a user who dismissed it.
	 */
	public function test_health_notice_hidden_once_dismissed(): void {
		update_option( 'woocommerce_feature_ai_assistant_experiments_enabled', 'no' );
		update_user_meta( get_current_user_id(), AiAssistantExperiments::NOTICE_DISMISSED_META, '1' );

		$this->assertNull( $this->view_with_report()['experiments'] );
	}

	/**
	 * @testdox A user who can't manage WooCommerce gets no notice.
	 */
	public function test_health_notice_hidden_without_manage_woocommerce(): void {
		update_option( 'woocommerce_feature_ai_assistant_experiments_enabled', 'no' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$this->assertNull( $this->view_with_report()['experiments'] );
	}

	/**
	 * @testdox Before the first check there's no Extra details card, so there's no notice above it either.
	 */
	public function test_health_notice_needs_a_report(): void {
		StoreOutputChecksTest::block_requests();
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		update_option( 'woocommerce_feature_ai_assistant_experiments_enabled', 'no' );
		delete_option( AgentReadiness::REPORT_OPTION );

		$this->assertNull( wc_get_container()->get( ReadinessCheckPage::class )->view()['experiments'] );
	}

	/**
	 * @testdox The Extra details card and the AI tools Overview don't mention the experiments.
	 */
	public function test_no_experiments_in_extra_details_or_overview(): void {
		update_option( 'woocommerce_feature_ai_assistant_experiments_enabled', 'no' );

		$view = $this->view_with_report();

		$this->assertArrayNotHasKey( 'experiments', $view['declared'] );
		$this->assertArrayNotHasKey( 'experiments', $view['overview'] );
	}

	/**
	 * Store a check report whose Extra details items have these results.
	 *
	 * @param array<string, bool|null> $found Whether each item, by id, was found.
	 */
	private function store_found( array $found ): void {
		$items = array();
		foreach ( $found as $id => $value ) {
			$items[] = array(
				'id'    => $id,
				'name'  => $id,
				'found' => $value,
			);
		}
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
				'declared'     => $items,
			)
		);
	}

	/**
	 * The AI shopping health view, after a check that found one item missing.
	 *
	 * @return array<string, mixed>
	 */
	private function view_with_report(): array {
		StoreOutputChecksTest::block_requests();
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
				'declared'     => array(
					array(
						'id'    => 'ai-txt',
						'name'  => 'ai.txt usage file',
						'found' => false,
					),
				),
			)
		);
		return wc_get_container()->get( ReadinessCheckPage::class )->view();
	}
}

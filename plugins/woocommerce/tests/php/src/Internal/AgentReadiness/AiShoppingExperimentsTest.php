<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\AiShoppingExperiments;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessCheckPage;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\PluginUtil;
use WC_Unit_Test_Case;

/**
 * Tests for the AI shopping experiments feature and its Features page row.
 */
class AiShoppingExperimentsTest extends WC_Unit_Test_Case {

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
			'Turn on newer ways for AI assistants to read and shop your store: WebMCP on store forms, Markdown versions of pages, a full-content llms.txt, a Markdown sitemap and AI catalog, and AI training notices. Uses your ' . $link . 'AI training choice</a>.',
			AiShoppingExperiments::get_description()
		);
	}

	/**
	 * @testdox The AI training sentence is left out when AI training notices are not in the list.
	 */
	public function test_description_omits_ai_training_sentence_without_ai_training_notices(): void {
		$experiments = AiShoppingExperiments::get_experiments();
		unset( $experiments[ AiShoppingExperiments::AI_TRAINING_NOTICES ] );

		$description = AiShoppingExperiments::get_description( $experiments );

		$this->assertSame(
			'Turn on newer ways for AI assistants to read and shop your store: WebMCP on store forms, Markdown versions of pages, a full-content llms.txt, and a Markdown sitemap and AI catalog.',
			$description
		);
	}

	/**
	 * @testdox The description joins two experiments without a comma.
	 */
	public function test_description_joins_two_experiments_with_and(): void {
		$description = AiShoppingExperiments::get_description(
			array(
				'first'  => array( 'First', array() ),
				'second' => array( 'Second', array() ),
			)
		);

		$this->assertSame( 'Turn on newer ways for AI assistants to read and shop your store: First and Second.', $description );
	}

	/**
	 * @testdox The feature is experimental, off by default, and skips plugin compatibility checks.
	 */
	public function test_feature_definition(): void {
		$definition = $this->sut->get_feature_definition( AiShoppingExperiments::FEATURE_NAME );

		$this->assertNotNull( $definition, 'The feature should be registered.' );
		$this->assertSame( 'AI shopping experiments', $definition['name'] );
		$this->assertFalse( $definition['enabled_by_default'], 'The feature should be off by default.' );
		$this->assertTrue( $definition['is_experimental'], 'The feature should be experimental.' );
		$this->assertTrue( $this->sut->should_skip_compatibility_checks( AiShoppingExperiments::FEATURE_NAME ), 'Plugin compatibility checks should be skipped.' );
		$this->assertFalse( $this->sut->feature_is_enabled( AiShoppingExperiments::FEATURE_NAME ), 'The feature should start off.' );
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
		$option_name = $this->sut->feature_enable_option_name( AiShoppingExperiments::FEATURE_NAME );

		$rows = array_values(
			array_filter(
				$this->sut->add_feature_settings( array(), 'features' ),
				static fn( $setting ) => ( $setting['id'] ?? null ) === $option_name
			)
		);

		$this->assertCount( $expected_rows, $rows );
		if ( $expected_rows ) {
			$this->assertSame( 'AI shopping experiments', $rows[0]['title'] );
			$this->assertSame( 'checkbox', $rows[0]['type'] );
			$this->assertSame( 'no', $rows[0]['default'], 'The row should default to off.' );
			$this->assertSame( AiShoppingExperiments::get_description(), $rows[0]['desc'] );
		}
	}

	/**
	 * @testdox The experiments add the ten Extra details items the card marks.
	 */
	public function test_declared_item_ids(): void {
		$this->assertSame(
			array( 'webmcp-forms-schema', 'markdown-pages', 'markdown-alternate-link', 'markdown-frontmatter', 'llms-full', 'sitemap-md', 'ai-catalog', 'ard-manifest', 'ai-txt', 'tdmrep' ),
			AiShoppingExperiments::get_declared_item_ids()
		);
	}

	/**
	 * @testdox While the feature is off, Extra details marks the items the experiments add and offers to add the missing ones.
	 */
	public function test_declared_offers_missing_items_while_off(): void {
		update_option( 'woocommerce_feature_ai_shopping_experiments_enabled', 'no' );

		$declared = $this->declared_view(
			array(
				'ai-txt'         => false,
				'tdmrep'         => null,
				'sitemap-md'     => true,
				'markdown-pages' => false,
				'agents-md'      => false,
			)
		);

		$this->assertSame(
			array( 'ai-txt', 'sitemap-md', 'markdown-pages', 'tdmrep' ),
			$this->experiment_names( $declared ),
			'Mapped items are marked, wherever they are grouped; other items are not'
		);
		$this->assertFalse( $declared['experiments']['enabled'] );
		$this->assertSame( 'Experiment', $declared['experiments']['tag'] );
		$this->assertSame( 'WooCommerce can add 2 of these with AI shopping experiments.', $declared['experiments']['offer']['title'], 'Counts only mapped items that were not found' );
		$this->assertSame( 'They’re marked below. These standards aren’t settled yet, and few AI assistants use them. You can turn them off at any time in Features.', $declared['experiments']['offer']['text'] );
		$this->assertSame( 'Turn on AI shopping experiments', $declared['experiments']['offer']['button'] );
		$this->assertSame( 'AI shopping experiments are on. Run the check again to see the new files.', $declared['experiments']['turned_on']['text'] );
		$this->assertSame( 'Run check', $declared['experiments']['turned_on']['button'] );
		$this->assertSame( 'AI shopping experiments weren’t turned on. Try again.', $declared['experiments']['error'] );
	}

	/**
	 * @testdox There is no offer when every item the experiments add is found or not checked.
	 */
	public function test_declared_has_no_offer_when_nothing_is_missing(): void {
		update_option( 'woocommerce_feature_ai_shopping_experiments_enabled', 'no' );

		$declared = $this->declared_view(
			array(
				'ai-txt'    => true,
				'tdmrep'    => null,
				'agents-md' => false,
			)
		);

		$this->assertNull( $declared['experiments']['offer'] );
	}

	/**
	 * @testdox Once the feature is on, Extra details says so and links to Features instead of offering it.
	 */
	public function test_declared_links_to_features_when_on(): void {
		update_option( 'woocommerce_feature_ai_shopping_experiments_enabled', 'yes' );

		$declared = $this->declared_view( array( 'ai-txt' => false ) );

		$this->assertTrue( $declared['experiments']['enabled'] );
		$this->assertNull( $declared['experiments']['offer'] );
		$this->assertSame( 'AI shopping experiments are on', $declared['experiments']['on']['text'] );
		$this->assertSame( 'Turn off in Features', $declared['experiments']['on']['link']['text'] );
		$this->assertSame( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=features' ), $declared['experiments']['on']['link']['href'] );
	}

	/**
	 * @testdox A user who can't manage WooCommerce gets no notice and no Experiment marks.
	 */
	public function test_declared_hides_experiments_without_manage_woocommerce(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$declared = $this->declared_view( array( 'ai-txt' => false ) );

		$this->assertNull( $declared['experiments'] );
		$this->assertSame( array(), $this->experiment_names( $declared ) );
	}

	/**
	 * The Extra details card from the view, for a stored report with these items.
	 *
	 * @param array<string, bool|null> $found Whether each item, by id, was found.
	 * @return array<string, mixed>
	 */
	private function declared_view( array $found ): array {
		StoreOutputChecksTest::block_requests();
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
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
		return wc_get_container()->get( ReadinessCheckPage::class )->view()['declared'];
	}

	/**
	 * Names of the items marked as added by the experiments, across groups.
	 *
	 * @param array<string, mixed> $declared The Extra details card.
	 * @return array<int, string>
	 */
	private function experiment_names( array $declared ): array {
		$names = array();
		foreach ( $declared['groups'] as $group ) {
			foreach ( $group['items'] as $item ) {
				if ( $item['experiment'] ) {
					$names[] = $item['name'];
				}
			}
		}
		return $names;
	}
}

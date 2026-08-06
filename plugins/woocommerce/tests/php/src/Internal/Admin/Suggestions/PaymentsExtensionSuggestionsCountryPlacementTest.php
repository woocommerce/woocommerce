<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Suggestions;

use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentsExtensionSuggestions;
use Automattic\WooCommerce\Internal\Utilities\ArrayUtil;
use Automattic\WooCommerce\RestApi\UnitTests\CorePayPalGatewayTrait;
use ReflectionClass;
use WC_Unit_Test_Case;

/**
 * Per-country, per-section payment suggestion placement contract test.
 *
 * @class PaymentsProviders
 */
class PaymentsExtensionSuggestionsCountryPlacementTest extends WC_Unit_Test_Case {
	use CorePayPalGatewayTrait;

	/**
	 * Fixture section keys, in the tracking sheet's row order.
	 */
	private const SECTION_KEYS = array(
		'primary_psp',
		'primary_apm',
		'primary_offline',
		'other_psp',
		'other_express_checkout',
		'other_bnpl',
		'other_crypto',
	);

	/**
	 * Suggestion category to fixture section key.
	 */
	private const CATEGORY_TO_SECTION = array(
		PaymentsProviders::CATEGORY_PSP              => 'other_psp',
		PaymentsProviders::CATEGORY_EXPRESS_CHECKOUT => 'other_express_checkout',
		PaymentsProviders::CATEGORY_BNPL             => 'other_bnpl',
		PaymentsProviders::CATEGORY_CRYPTO           => 'other_crypto',
	);

	/**
	 * Full Stack markets where PayPal Wallet is deliberately unavailable.
	 */
	private const PAYPAL_WALLET_UNAVAILABLE_COUNTRIES = array( 'CN', 'GE', 'KZ' );

	/**
	 * Preferred suggestion type to fixture primary slot.
	 *
	 * The preferred APM slot takes express checkouts too, so both types map to it.
	 */
	private const TYPE_TO_PRIMARY_SLOT = array(
		PaymentsExtensionSuggestions::TYPE_PSP => 'primary_psp',
		PaymentsExtensionSuggestions::TYPE_APM => 'primary_apm',
		PaymentsExtensionSuggestions::TYPE_EXPRESS_CHECKOUT => 'primary_apm',
	);

	/**
	 * The loaded placement fixture.
	 *
	 * Memoised because `require` returns a value, so `require_once` cannot be used
	 * and every call would otherwise re-parse the file.
	 *
	 * @var array<string, array>|null
	 */
	private static ?array $fixture = null;

	/**
	 * System under test.
	 *
	 * @var PaymentsProviders
	 */
	protected PaymentsProviders $sut;

	/**
	 * The raw suggestion catalog, for the tests that read it directly.
	 *
	 * @var PaymentsExtensionSuggestions
	 */
	protected PaymentsExtensionSuggestions $suggestions;

	/**
	 * The ID of the store admin user.
	 *
	 * @var int
	 */
	protected int $store_admin_id;

	/**
	 * Set up the baseline store state.
	 *
	 * Baseline means: an admin who may install plugins, an enabled ecommerce
	 * gateway so Express/BNPL/Crypto suggestions are not suppressed, the
	 * online-equivalent profiler state, nothing hidden, and no saved order.
	 *
	 * Everything written here lands inside the per-test transaction that
	 * WP_UnitTestCase opens, so no option or user meta needs restoring by hand.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->store_admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->store_admin_id );

		// The online-equivalent profiler state.
		delete_option( OnboardingProfile::DATA_OPTION );

		$this->suggestions = wc_get_container()->get( PaymentsExtensionSuggestions::class );
		$this->sut         = wc_get_container()->get( PaymentsProviders::class );

		// Satisfy the enabled-ecommerce-gateway rule, which otherwise suppresses the
		// Express/BNPL/Crypto suggestions. Core PayPal is not a suggested extension,
		// so it does not suppress the PayPal suggestions. This also clears the cache.
		$this->enable_core_paypal_pg();
	}

	/**
	 * The payment providers service the core PayPal gateway helpers must invalidate.
	 *
	 * @return PaymentsProviders
	 */
	protected function get_payments_providers_service(): PaymentsProviders {
		return $this->sut;
	}

	/**
	 * Restore the in-process state this class leaks into the next one.
	 *
	 * The gateway singleton and the provider caches outlive the per-test
	 * transaction, so they have to be rebuilt — but once, after the class, not
	 * after every test. setUp() rebuilds both anyway, so a per-test rebuild is
	 * thrown away 367 times out of 368.
	 *
	 * This is also the only place the rebuild is correct. tear_down() rolls the
	 * transaction back but does not flush the object cache, so a rebuild there
	 * reads the option values the test just wrote and hands the next class a
	 * PayPal gateway still marked enabled. tear_down_after_class() flushes the
	 * cache before this runs, so init() sees what is actually in the database.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		self::reload_payment_gateways();

		wc_get_container()->get( PaymentsProviders::class )->clear_cache();
	}

	/**
	 * @testdox The baseline state surfaces the complete Other catalog.
	 */
	public function test_baseline_state_surfaces_the_complete_other_catalog(): void {
		$result = $this->sut->get_extension_suggestions( 'US' );
		$other  = array_column( $result['other'], 'id' );

		$this->assertContains(
			'amazon_pay',
			$other,
			'With an enabled ecommerce gateway, the non-preferred express checkout must surface. If this fails, the baseline state is not established and every other assertion in this class is meaningless.'
		);
		$this->assertContains( 'affirm', $other, 'BNPL suggestions must surface in the baseline state.' );

		$preferred = array_column( $result['preferred'], 'id' );
		$this->assertContains(
			'paypal_full_stack',
			$preferred,
			'Enabling the core PayPal gateway must not suppress the PayPal suggestion.'
		);
	}

	/**
	 * @testdox The fixture covers exactly the countries the source configures.
	 */
	public function test_fixture_covers_every_configured_country(): void {
		$reflection = new ReflectionClass( PaymentsExtensionSuggestions::class );
		$property   = $reflection->getProperty( 'country_extensions' );
		$property->setAccessible( true );

		$configured = array_keys( $property->getValue( $this->suggestions ) );
		$fixtured   = array_keys( self::load_fixture() );
		sort( $configured );
		sort( $fixtured );

		$missing = array_diff( $configured, $fixtured );
		$extra   = array_diff( $fixtured, $configured );

		$this->assertSame(
			array(),
			array_values( $missing ),
			'These countries are configured in PaymentsExtensionSuggestions but absent from the fixture, so their placement is untested: ' . implode( ', ', $missing )
		);
		$this->assertSame(
			array(),
			array_values( $extra ),
			'These countries are in the fixture but no longer configured: ' . implode( ', ', $extra )
		);
	}

	/**
	 * @testdox No suggested extension is active in the test environment.
	 */
	public function test_no_suggested_extension_is_active(): void {
		$suggested_slugs = array();
		foreach ( array_keys( self::load_fixture() ) as $country ) {
			foreach ( $this->suggestions->get_country_extensions( $country ) as $extension ) {
				$slug = $extension['plugin']['slug'] ?? '';
				if ( '' !== $slug ) {
					$suggested_slugs[ $slug ] = true;
				}
			}
		}

		$active_suggested = array();
		foreach ( PluginsHelper::get_active_plugin_slugs() as $active_slug ) {
			$slug = Utils::normalize_plugin_slug( $active_slug );
			if ( isset( $suggested_slugs[ $slug ] ) ) {
				$active_suggested[ $slug ] = true;
			}
		}
		$active_suggested = array_keys( $active_suggested );
		sort( $active_suggested );

		$this->assertSame(
			array(),
			$active_suggested,
			'These suggested extensions are active in the test environment: ' . implode( ', ', $active_suggested ) . '. An active suggested extension is dropped from the suggestions, so every country that lists it will fail. Deactivate it, or remove it from the test environment configuration.'
		);
	}

	/**
	 * @testdox PayPal Wallet remains available when PayPal Full Stack is hidden.
	 *
	 * @dataProvider data_provider_paypal_wallet_fallback_countries
	 *
	 * @param string $country The country code.
	 */
	public function test_paypal_wallet_remains_available_when_full_stack_is_hidden( string $country ): void {
		update_user_meta(
			$this->store_admin_id,
			Payments::PAYMENTS_NOX_PROFILE_KEY,
			array(
				'hidden_suggestions' => array(
					array(
						'id'        => PaymentsExtensionSuggestions::PAYPAL_FULL_STACK,
						'timestamp' => time(),
					),
				),
			)
		);
		$this->sut->clear_cache();

		$projected = $this->project_sections( $this->sut->get_extension_suggestions( $country ), $country );

		$this->assertContains(
			PaymentsExtensionSuggestions::PAYPAL_WALLET,
			$projected['other_express_checkout'] ?? array(),
			"PayPal Wallet must remain available as an Other express checkout fallback in $country when PayPal Full Stack is hidden."
		);
	}

	/**
	 * Data provider yielding fixture countries that expect Full Stack and support Wallet fallback.
	 *
	 * @return array<string, array{string}>
	 */
	public function data_provider_paypal_wallet_fallback_countries(): array {
		$cases = array();
		foreach ( self::load_fixture() as $country => $expected ) {
			if ( in_array( $country, self::PAYPAL_WALLET_UNAVAILABLE_COUNTRIES, true ) ) {
				continue;
			}

			if ( self::fixture_contains_suggestion( $expected, PaymentsExtensionSuggestions::PAYPAL_FULL_STACK ) ) {
				$cases[ $country ] = array( $country );
			}
		}

		return $cases;
	}

	/**
	 * @testdox The fixture defines countries that expect PayPal Full Stack.
	 */
	public function test_paypal_wallet_fallback_country_provider_is_not_empty(): void {
		$this->assertNotEmpty(
			$this->data_provider_paypal_wallet_fallback_countries(),
			'The PayPal Wallet fallback contract is checking nothing because no fixture country expects PayPal Full Stack.'
		);
	}

	/**
	 * @testdox Raw PayPal Wallet exclusions match the explicit unavailable countries.
	 */
	public function test_paypal_wallet_unavailable_countries_match_raw_catalog(): void {
		$actual_unavailable_countries = array();

		foreach ( self::load_fixture() as $country => $expected ) {
			if ( ! self::fixture_contains_suggestion( $expected, PaymentsExtensionSuggestions::PAYPAL_FULL_STACK ) ) {
				continue;
			}

			$raw_ids = array_column( $this->suggestions->get_country_extensions( $country ), 'id' );
			if ( ! in_array( PaymentsExtensionSuggestions::PAYPAL_WALLET, $raw_ids, true ) ) {
				$actual_unavailable_countries[] = $country;
			}
		}

		$expected_unavailable_countries = self::PAYPAL_WALLET_UNAVAILABLE_COUNTRIES;
		sort( $expected_unavailable_countries );
		sort( $actual_unavailable_countries );

		$no_longer_unavailable = array_diff( $expected_unavailable_countries, $actual_unavailable_countries );
		$newly_unavailable     = array_diff( $actual_unavailable_countries, $expected_unavailable_countries );
		$no_longer_message     = empty( $no_longer_unavailable ) ? '(none)' : implode( ', ', $no_longer_unavailable );
		$newly_message         = empty( $newly_unavailable ) ? '(none)' : implode( ', ', $newly_unavailable );

		$this->assertSame(
			$expected_unavailable_countries,
			$actual_unavailable_countries,
			'Raw PayPal Wallet availability does not match PAYPAL_WALLET_UNAVAILABLE_COUNTRIES. '
			. "Countries where Wallet is now available: $no_longer_message. "
			. "Countries newly missing Wallet: $newly_message."
		);
	}

	/**
	 * Data provider yielding one case per country in the fixture.
	 *
	 * @return array<string, array{string, array}>
	 */
	public function data_provider_country_placements(): array {
		$cases = array();
		foreach ( self::load_fixture() as $country => $expected ) {
			$cases[ $country ] = array( $country, $expected );
		}

		return $cases;
	}

	/**
	 * Data provider yielding only the fixture countries that declare an offline provider.
	 *
	 * @return array<string, array{string, array}>
	 */
	public function data_provider_offline_country_placements(): array {
		$cases = array();
		foreach ( self::load_fixture() as $country => $expected ) {
			if ( array_key_exists( 'primary_offline', $expected ) ) {
				$cases[ $country ] = array( $country, $expected );
			}
		}

		return $cases;
	}

	/**
	 * @testdox The fixture defines countries that declare an offline provider.
	 */
	public function test_offline_country_provider_is_not_empty(): void {
		$this->assertNotEmpty(
			$this->data_provider_offline_country_placements(),
			'The offline promotion contract is checking nothing because no fixture country declares an offline provider.'
		);
	}

	/**
	 * @testdox Each country's baseline placement matches the fixture.
	 *
	 * @dataProvider data_provider_country_placements
	 *
	 * @param string $country  The country code.
	 * @param array  $expected The expected section map.
	 */
	public function test_country_baseline_placement( string $country, array $expected ): void {
		$result    = $this->sut->get_extension_suggestions( $country );
		$projected = $this->project_sections( $result, $country );
		$this->assert_no_preferred_tag_in_other( $result, $country );
		$this->assert_valid_section_map( $expected, $country, 'fixture' );
		$this->assert_valid_section_map( $projected, $country, 'code projection' );

		// The offline slot is filled only in the offline profiler state, so it is
		// not part of the baseline expectation. Assert it is genuinely empty here
		// and check the promotion separately.
		$this->assertArrayNotHasKey(
			'primary_offline',
			$projected,
			"For $country, the offline slot must be empty in the baseline state."
		);

		$this->assertSame(
			$this->normalise( self::baseline_of( $expected ) ),
			$this->normalise( $projected ),
			$this->describe_mismatch( $country, $expected, $projected )
		);
	}

	/**
	 * @testdox Countries with an offline provider promote it in the offline state.
	 *
	 * @dataProvider data_provider_offline_country_placements
	 *
	 * @param string $country  The country code.
	 * @param array  $expected The expected section map.
	 */
	public function test_country_offline_placement( string $country, array $expected ): void {
		$offline_id = $expected['primary_offline'];
		$this->assertContains(
			$offline_id,
			$expected['other_psp'] ?? array(),
			"The fixture declares '$offline_id' as $country's offline provider, but it is absent from that entry's other_psp. The offline expectation is derived by moving it out of other_psp, so the entry is inconsistent — fix the fixture."
		);

		update_option(
			OnboardingProfile::DATA_OPTION,
			array(
				'business_choice'       => 'im_already_selling',
				'selling_online_answer' => 'no_im_selling_offline',
			)
		);
		$this->sut->clear_cache();

		$result    = $this->sut->get_extension_suggestions( $country );
		$projected = $this->project_sections( $result, $country );
		$derived   = $expected;
		$this->assert_no_preferred_tag_in_other( $result, $country );
		$derived['other_psp'] = array_values(
			array_filter(
				$expected['other_psp'],
				static function ( string $suggestion_id ) use ( $offline_id ): bool {
					return $offline_id !== $suggestion_id;
				}
			)
		);

		$this->assert_valid_section_map( $derived, $country, 'derived offline expectation' );
		$this->assert_valid_section_map( $projected, $country, 'offline code projection' );
		$this->assertSame(
			$this->normalise( $derived ),
			$this->normalise( $projected ),
			"Offline placement for $country does not match the expectation derived from its fixture entry. Fix the baseline entry first — the offline view is derived from it by moving '$offline_id' out of other_psp."
		);
	}

	/**
	 * Project a get_extension_suggestions() result into the fixture's section shape.
	 *
	 * Preferred slots are assigned by tag and type, never by array position: the
	 * preferred list is compacted, so index carries no meaning. VE and CN both
	 * return a single 'paypal_full_stack' entry, but VE's is an APM and CN's is a
	 * type-overridden PSP.
	 *
	 * @param array  $result  The service result.
	 * @param string $country The country code, for failure messages.
	 *
	 * @return array The projected section map.
	 */
	private function project_sections( array $result, string $country ): array {
		$projected = array();

		foreach ( $result['preferred'] as $suggestion ) {
			$tags = $suggestion['tags'] ?? array();

			$slot = in_array( PaymentsExtensionSuggestions::TAG_PREFERRED_OFFLINE, $tags, true )
				? 'primary_offline'
				: ( self::TYPE_TO_PRIMARY_SLOT[ $suggestion['_type'] ] ?? null );

			if ( null === $slot ) {
				$this->fail(
					"For $country, the preferred suggestion '{$suggestion['id']}' has type '{$suggestion['_type']}', which maps to no fixture slot."
				);
			}

			$this->assertArrayNotHasKey(
				$slot,
				$projected,
				"For $country, the '$slot' slot was filled twice; the second was '{$suggestion['id']}'."
			);

			$projected[ $slot ] = $suggestion['id'];
		}

		foreach ( $result['other'] as $suggestion ) {
			$category = $suggestion['category'] ?? '';

			$this->assertArrayHasKey(
				$category,
				self::CATEGORY_TO_SECTION,
				"For $country, the Other suggestion '{$suggestion['id']}' has category '$category', which maps to no fixture section. An uncategorised suggestion would silently vanish from the contract."
			);

			$projected[ self::CATEGORY_TO_SECTION[ $category ] ][] = $suggestion['id'];
		}

		return $projected;
	}

	/**
	 * Assert that nothing in the Other list kept the tag it lost its slot with.
	 *
	 * The tag is part of the REST payload and the fixture records IDs only, so
	 * nothing else here would notice. Countries that surface a preferred-by-default
	 * extension in Other strip the tag with a '_remove' override.
	 *
	 * This holds only while nothing is hidden. A hidden preferred suggestion skips
	 * the slot assignment entirely and reaches Other with its tags untouched, so
	 * tests that hide a suggestion must not call this.
	 *
	 * @param array  $result  The service result.
	 * @param string $country The country code, for failure messages.
	 */
	private function assert_no_preferred_tag_in_other( array $result, string $country ): void {
		foreach ( $result['other'] as $suggestion ) {
			$this->assertNotContains(
				PaymentsExtensionSuggestions::TAG_PREFERRED,
				$suggestion['tags'] ?? array(),
				"For $country, the Other suggestion '{$suggestion['id']}' still carries the preferred tag even though another provider filled its preferred slot. Strip it with a '_remove' override on the country entry."
			);
		}
	}

	/**
	 * Assert that a section map follows the placement fixture schema.
	 *
	 * @param array  $sections The section map.
	 * @param string $country  The country code.
	 * @param string $source   The source of the section map.
	 */
	private function assert_valid_section_map( array $sections, string $country, string $source ): void {
		$unknown_keys = array_diff( array_keys( $sections ), self::SECTION_KEYS );

		$this->assertSame(
			array(),
			$unknown_keys,
			"For $country, the $source contains unknown section keys: " . implode( ', ', $unknown_keys ) . '.'
		);

		foreach ( $sections as $key => $value ) {
			if ( 0 === strpos( $key, 'primary_' ) ) {
				$this->assertTrue(
					is_string( $value ) && '' !== $value,
					"For $country, the $source section '$key' must be a non-empty suggestion ID string."
				);
				continue;
			}

			$is_non_empty_list = is_array( $value )
				&& array() !== $value
				&& ArrayUtil::array_is_list( $value );

			$this->assertTrue(
				$is_non_empty_list,
				"For $country, the $source section '$key' must be a non-empty ordered list of suggestion ID strings."
			);

			foreach ( $value as $index => $suggestion_id ) {
				$this->assertTrue(
					is_string( $suggestion_id ) && '' !== $suggestion_id,
					"For $country, the $source section '$key' item at index $index must be a non-empty suggestion ID string."
				);
			}
		}
	}

	/**
	 * Order a section map into canonical key order so comparisons are stable.
	 *
	 * @param array $sections The section map.
	 *
	 * @return array The same data in canonical key order.
	 */
	private function normalise( array $sections ): array {
		$ordered = array();
		foreach ( self::SECTION_KEYS as $key ) {
			if ( array_key_exists( $key, $sections ) ) {
				$ordered[ $key ] = $sections[ $key ];
			}
		}

		return $ordered;
	}

	/**
	 * Build the assertion message for a country mismatch.
	 *
	 * The message ends with the fixture entry the code currently produces, in
	 * canonical form, so an intentional change can be pasted in directly.
	 *
	 * @param string $country   The country code.
	 * @param array  $expected  The fixture entry.
	 * @param array  $projected The projected actual sections.
	 *
	 * @return string
	 */
	private function describe_mismatch( string $country, array $expected, array $projected ): string {
		$lines             = array(
			"Placement for $country does not match the fixture.",
			'(If many countries fail at once, check test_no_suggested_extension_is_active first.)',
			'',
		);
		$baseline_expected = self::baseline_of( $expected );

		foreach ( self::SECTION_KEYS as $key ) {
			$want = $baseline_expected[ $key ] ?? null;
			$got  = $projected[ $key ] ?? null;

			if ( $want === $got ) {
				continue;
			}

			$lines[] = sprintf(
				'  %s: fixture %s, code %s',
				$key,
				null === $want ? '(absent)' : ( is_array( $want ) ? '[' . implode( ', ', $want ) . ']' : "'$want'" ),
				null === $got ? '(absent)' : ( is_array( $got ) ? '[' . implode( ', ', $got ) . ']' : "'$got'" )
			);
		}

		$lines[] = '';
		$lines[] = 'If this change is intentional, replace the entry with:';
		$lines[] = '';
		$lines[] = $this->render_fixture_entry( $country, $projected, $expected['primary_offline'] ?? null );
		$lines[] = '';
		$lines[] = 'Then run: pnpm --filter=@woocommerce/plugin-woocommerce lint:php:fix -- tests/php/src/Internal/Admin/Suggestions/fixtures/expected-country-placements.php';

		return implode( "\n", $lines );
	}

	/**
	 * Render a fixture entry in canonical form, ready to paste.
	 *
	 * @param string      $country         The country code.
	 * @param array       $sections        The section map to render.
	 * @param string|null $primary_offline The declared offline provider. It is
	 *                                     preserved only while it remains in the
	 *                                     projected baseline's other_psp list; no
	 *                                     replacement is inferred.
	 *
	 * @return string
	 */
	private function render_fixture_entry( string $country, array $sections, ?string $primary_offline ): string {
		if ( null !== $primary_offline && in_array( $primary_offline, $sections['other_psp'] ?? array(), true ) ) {
			$sections['primary_offline'] = $primary_offline;
		}
		$sections = $this->normalise( $sections );

		if ( empty( $sections ) ) {
			return "\t" . self::render_php_string_literal( $country ) . ' => array(),';
		}

		$width = max( array_map( 'strlen', array_keys( $sections ) ) );
		$lines = array( "\t" . self::render_php_string_literal( $country ) . ' => array(' );

		foreach ( $sections as $key => $value ) {
			$key_literal = self::render_php_string_literal( $key );
			$pad         = str_repeat( ' ', $width - strlen( $key ) );

			if ( is_array( $value ) ) {
				$lines[] = "\t\t$key_literal$pad => array(";
				foreach ( $value as $id ) {
					$lines[] = "\t\t\t" . self::render_php_string_literal( $id ) . ',';
				}
				$lines[] = "\t\t),";
			} else {
				$lines[] = "\t\t$key_literal$pad => " . self::render_php_string_literal( $value ) . ',';
			}
		}

		$lines[] = "\t),";

		return implode( "\n", $lines );
	}

	/**
	 * Render a value as a PHP single-quoted string literal.
	 *
	 * @param string $value The value to render.
	 *
	 * @return string
	 */
	private static function render_php_string_literal( string $value ): string {
		$escaped = str_replace(
			array( '\\', "'" ),
			array( '\\\\', "\\'" ),
			$value
		);

		return "'$escaped'";
	}

	/**
	 * The baseline view of a fixture entry.
	 *
	 * The offline slot is filled only in the offline profiler state, so it is not
	 * part of what the baseline comparison expects.
	 *
	 * @param array $sections The fixture section map.
	 *
	 * @return array The same map without the offline slot.
	 */
	private static function baseline_of( array $sections ): array {
		unset( $sections['primary_offline'] );

		return $sections;
	}

	/**
	 * Whether a fixture section map contains a suggestion ID.
	 *
	 * @param array  $sections      The fixture section map.
	 * @param string $suggestion_id The suggestion ID.
	 *
	 * @return bool
	 */
	private static function fixture_contains_suggestion( array $sections, string $suggestion_id ): bool {
		foreach ( $sections as $section ) {
			$section_ids = is_array( $section ) ? $section : array( $section );
			if ( in_array( $suggestion_id, $section_ids, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Load the placement fixture.
	 *
	 * @return array<string, array> Country code to expected section map.
	 */
	private static function load_fixture(): array {
		if ( null === self::$fixture ) {
			self::$fixture = require __DIR__ . '/fixtures/expected-country-placements.php';
		}

		return self::$fixture;
	}
}

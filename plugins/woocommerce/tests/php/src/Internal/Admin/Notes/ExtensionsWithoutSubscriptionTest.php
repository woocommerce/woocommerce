<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Notes;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Internal\Admin\Notes\ExtensionsWithoutSubscription;
use WC_Helper;
use WC_Helper_Options;
use WC_Unit_Test_Case;

/**
 * Tests for the ExtensionsWithoutSubscription class.
 */
class ExtensionsWithoutSubscriptionTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ExtensionsWithoutSubscription
	 */
	private $sut;

	/**
	 * Filter standing in for WooCommerce.com, removed on tear down.
	 *
	 * @var callable|null
	 */
	private $http_filter;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$_GET['page'] = 'wc-admin';
		WC_Helper::flush_local_woo_products_cache();
		$this->sut = new ExtensionsWithoutSubscription();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			// A request global and a static cache in WC_Helper, which no base class resets.
			unset( $_GET['page'], $_GET['wc-helper-status'] );
			WC_Helper::flush_local_woo_products_cache();
			$this->reset_current_page();
			if ( $this->http_filter ) {
				remove_filter( 'pre_http_request', $this->http_filter );
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should name the extension when a single extension has no subscription.
	 */
	public function test_single_extension_note_names_the_extension(): void {
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );

		$this->sut->refresh_note();

		$note = $this->get_saved_note();
		$this->assertSame( "Product Add-Ons doesn't have an active subscription", $note->get_title(), 'The title should name the extension.' );
		$this->assertStringContainsString( 'keep it protected', $note->get_content(), 'The content should use the singular copy.' );
	}

	/**
	 * @testdox Should use the multiple extensions copy when more than one extension has no subscription.
	 */
	public function test_multiple_extensions_note_uses_plural_copy(): void {
		$this->mock_active_woo_plugins( array( 'Product Add-Ons', 'Store Credit' ) );

		$this->sut->refresh_note();

		$note = $this->get_saved_note();
		$this->assertSame( "Some of your extensions don't have an active subscription", $note->get_title(), 'The title should not name a single extension.' );
		$this->assertStringContainsString( 'keep them protected', $note->get_content(), 'The content should use the plural copy.' );
	}

	/**
	 * @testdox Should link the Subscribe action to the in-app My Subscriptions page.
	 */
	public function test_subscribe_action_links_to_my_subscriptions(): void {
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );

		$this->sut->refresh_note();

		$actions = $this->get_saved_note()->get_actions();
		$this->assertCount( 1, $actions, 'The note should have one action.' );
		$this->assertSame( 'subscribe', $actions[0]->name, 'The action should be Subscribe.' );
		$this->assertStringStartsWith( admin_url( 'admin.php?page=wc-admin&tab=my-subscriptions' ), $actions[0]->query, 'The action should open My Subscriptions in wp-admin.' );
		$this->assertStringContainsString( 'utm_source=inbox_notification&utm_campaign=pu_inbox_purchase', $actions[0]->query, 'The action should carry the inbox UTM params.' );
	}

	/**
	 * @testdox Should update an existing note when the set of extensions changes, keeping it dismissed.
	 */
	public function test_existing_note_is_updated_and_stays_dismissed(): void {
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );
		$this->sut->refresh_note();
		$note = $this->get_saved_note();
		$note->set_is_deleted( true );
		$note->save();

		$this->mock_active_woo_plugins( array( 'Product Add-Ons', 'Store Credit' ) );
		$this->sut->refresh_note();

		$note = $this->get_saved_note();
		$this->assertSame( "Some of your extensions don't have an active subscription", $note->get_title(), 'The note should follow the new set of extensions.' );
		$this->assertTrue( $note->get_is_deleted(), 'A dismissed note should stay dismissed.' );
	}

	/**
	 * @testdox Should delete the note once every extension has a subscription.
	 */
	public function test_note_is_deleted_when_resolved(): void {
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );
		$this->sut->refresh_note();

		$this->mock_active_woo_plugins( array() );
		$this->sut->refresh_note();

		$this->assertFalse( ExtensionsWithoutSubscription::note_exists(), 'The note should be removed once nothing is missing a subscription.' );
	}

	/**
	 * @testdox Should keep a dismissed note once resolved, so it stays dismissed if the extension comes back.
	 */
	public function test_dismissed_note_stays_dismissed_after_resolving_and_returning(): void {
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );
		$this->sut->refresh_note();
		$note = $this->get_saved_note();
		$note->set_is_deleted( true );
		$note->save();

		$this->mock_active_woo_plugins( array() );
		$this->sut->refresh_note();
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );
		$this->sut->refresh_note();

		$this->assertTrue( $this->get_saved_note()->get_is_deleted(), 'Deactivating and reactivating the extension should not bring back a dismissed note.' );
	}

	/**
	 * @testdox Should keep an actioned note once resolved, so it stays actioned if the extension comes back.
	 */
	public function test_actioned_note_stays_actioned_after_resolving_and_returning(): void {
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );
		$this->sut->refresh_note();
		$note = $this->get_saved_note();
		$note->set_status( Note::E_WC_ADMIN_NOTE_ACTIONED );
		$note->save();

		$this->mock_active_woo_plugins( array() );
		$this->sut->refresh_note();
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );
		$this->sut->refresh_note();

		$this->assertSame( Note::E_WC_ADMIN_NOTE_ACTIONED, $this->get_saved_note()->get_status(), 'Deactivating and reactivating the extension should not bring back an actioned note.' );
	}

	/**
	 * @testdox Should keep the note while a connected store's subscriptions fetch is failing.
	 */
	public function test_note_is_kept_while_the_api_is_failing(): void {
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );
		$this->sut->refresh_note();

		WC_Helper_Options::update( 'auth', array( 'access_token' => 'token' ) );
		set_transient( '_woocommerce_helper_subscriptions', array(), HOUR_IN_SECONDS );
		set_transient(
			'_woocommerce_helper_subscriptions_api_error',
			array(
				'code'    => 429,
				'message' => 'Rate limited',
			),
			HOUR_IN_SECONDS
		);
		$this->sut->refresh_note();

		$this->assertTrue( ExtensionsWithoutSubscription::note_exists(), 'An unknown subscription state should not remove the note.' );
	}

	/**
	 * @testdox Should delete the note once no extension is active, even while the subscriptions fetch is failing.
	 */
	public function test_note_is_deleted_when_no_extension_is_active_while_the_api_is_failing(): void {
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );
		$this->sut->refresh_note();

		$this->mock_active_woo_plugins( array() );
		WC_Helper_Options::update( 'auth', array( 'access_token' => 'token' ) );
		set_transient( '_woocommerce_helper_subscriptions', array(), HOUR_IN_SECONDS );
		set_transient(
			'_woocommerce_helper_subscriptions_api_error',
			array(
				'code'    => 429,
				'message' => 'Rate limited',
			),
			HOUR_IN_SECONDS
		);
		$this->sut->refresh_note();

		$this->assertFalse( ExtensionsWithoutSubscription::note_exists(), 'With no active extension, the note is obsolete whatever the subscription state.' );
	}

	/**
	 * @testdox Should fetch the subscription list of a connected store at most once a day.
	 */
	public function test_connected_store_fetches_subscriptions_once_a_day(): void {
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );
		$this->connect_store();
		$requests = $this->mock_subscription_requests();

		$this->sut->refresh_note();

		$this->assertSame( 1, $requests->count, 'The first refresh of the day may fetch the list.' );
		$this->assertTrue( ExtensionsWithoutSubscription::note_exists(), 'A fetched empty list means the extension has no subscription.' );

		WC_Helper::_flush_subscriptions_cache();
		$this->mock_active_woo_plugins( array( 'Product Add-Ons', 'Bookings' ) );
		$this->sut->refresh_note();

		$this->assertSame( 1, $requests->count, 'A second cold-cache refresh the same day must not block the page on a fetch.' );
		$this->assertSame( array( 'woo-test-plugin-0/woo-test-plugin-0.php' ), $this->get_saved_note()->get_content_data()->plugins, 'The note is left alone until the list can be read cheaply.' );
	}

	/**
	 * @testdox Should follow the cached subscription list without fetching once the daily fetch is spent.
	 */
	public function test_note_follows_the_cached_subscription_list_without_fetching(): void {
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );
		$this->connect_store();
		update_option( ExtensionsWithoutSubscription::LAST_FETCH_OPTION_KEY, time() );
		set_transient( '_woocommerce_helper_subscriptions', array(), HOUR_IN_SECONDS );
		$requests = $this->mock_subscription_requests();

		$this->sut->refresh_note();

		$this->assertSame( 0, $requests->count, 'A cached list needs no fetch.' );
		$this->assertTrue( ExtensionsWithoutSubscription::note_exists(), 'The cached list shows the extension has no subscription.' );
	}

	/**
	 * @testdox Should remove the note once no extension is active, without a fetch.
	 */
	public function test_note_is_deleted_when_no_extension_is_active_without_fetching(): void {
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );
		$this->sut->refresh_note();

		$this->mock_active_woo_plugins( array() );
		$this->connect_store();
		update_option( ExtensionsWithoutSubscription::LAST_FETCH_OPTION_KEY, time() );
		$requests = $this->mock_subscription_requests();
		$this->sut->refresh_note();

		$this->assertSame( 0, $requests->count, 'With nothing to check there is nothing to fetch.' );
		$this->assertFalse( ExtensionsWithoutSubscription::note_exists(), 'An empty result is definitive whatever the cache state.' );
	}

	/**
	 * @testdox Should fetch right after the store connects, even when the daily fetch is spent.
	 */
	public function test_note_fetches_right_after_connecting(): void {
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );
		$this->connect_store();
		update_option( ExtensionsWithoutSubscription::LAST_FETCH_OPTION_KEY, time() );
		$_GET['wc-helper-status'] = 'helper-connected';
		$requests                 = $this->mock_subscription_requests();

		$this->sut->refresh_note();

		$this->assertSame( 1, $requests->count, 'Connecting changes the answer, so the list is fetched.' );
		$this->assertTrue( ExtensionsWithoutSubscription::note_exists(), 'The fresh list shows the extension has no subscription.' );
	}

	/**
	 * @testdox Should run no database queries while the extensions without a subscription haven't changed.
	 */
	public function test_unchanged_refresh_runs_no_queries(): void {
		global $wpdb;
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );
		$this->sut->refresh_note();

		$queries_before = $wpdb->num_queries;
		$this->sut->refresh_note();

		$this->assertSame( 0, $wpdb->num_queries - $queries_before, 'A refresh that changes nothing should not hit the database.' );
		$this->assertTrue( ExtensionsWithoutSubscription::note_exists(), 'The note should still be there.' );
	}

	/**
	 * @testdox Should run no database queries on a connected store without WooCommerce.com extensions.
	 */
	public function test_connected_store_without_extensions_runs_no_queries(): void {
		global $wpdb;
		$this->mock_active_woo_plugins( array() );
		$this->connect_store();
		$requests = $this->mock_subscription_requests();
		$this->sut->refresh_note();

		$queries_before = $wpdb->num_queries;
		$this->sut->refresh_note();

		$this->assertSame( 0, $wpdb->num_queries - $queries_before, 'With nothing to check there is nothing to read.' );
		$this->assertSame( 0, $requests->count, 'With nothing to check there is nothing to fetch.' );
	}

	/**
	 * @testdox Should do nothing outside WooCommerce admin pages.
	 */
	public function test_note_is_not_added_outside_woocommerce_pages(): void {
		$this->mock_active_woo_plugins( array( 'Product Add-Ons' ) );
		unset( $_GET['page'] );
		set_current_screen( 'dashboard' );
		$this->reset_current_page();

		$this->sut->refresh_note();

		$this->assertFalse( ExtensionsWithoutSubscription::note_exists(), 'The note should only be refreshed on WooCommerce admin pages.' );
	}

	/**
	 * Makes the given Woo extensions installed and active on a store that isn't connected to WooCommerce.com.
	 *
	 * @param string[] $names Extension names.
	 * @return void
	 */
	private function mock_active_woo_plugins( array $names ): void {
		$plugins = array();
		foreach ( $names as $index => $name ) {
			$plugin_file             = 'woo-test-plugin-' . $index . '/woo-test-plugin-' . $index . '.php';
			$plugins[ $plugin_file ] = array(
				'Name'    => $name,
				'Version' => '1.0.0',
				'Woo'     => ( 100 + $index ) . ':abc' . $index,
			);
		}

		wp_cache_set( 'plugins', array( '' => $plugins ), 'plugins' );
		update_option( 'active_plugins', array_keys( $plugins ) );
		WC_Helper::flush_local_woo_products_cache();
	}

	/**
	 * Connects the store to WooCommerce.com with credentials WC_Helper_API can sign requests with.
	 *
	 * @return void
	 */
	private function connect_store(): void {
		WC_Helper_Options::update(
			'auth',
			array(
				'site_id'             => 45,
				'access_token'        => 'token',
				'access_token_secret' => 'secret',
			)
		);
	}

	/**
	 * Answers WooCommerce.com subscription requests with an empty list and counts them.
	 *
	 * @return object Holds the request count.
	 */
	private function mock_subscription_requests(): object {
		$requests = (object) array( 'count' => 0 );

		$this->http_filter = static function ( $preempt, $args, $url ) use ( $requests ) {
			if ( false === strpos( $url, '/subscriptions' ) ) {
				return $preempt;
			}
			++$requests->count;

			return array(
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'body'     => '[]',
			);
		};
		add_filter( 'pre_http_request', $this->http_filter, 10, 3 );

		return $requests;
	}

	/**
	 * Makes the page controller resolve the current page again from the current screen.
	 *
	 * The PageController singleton caches the page it first resolved for the whole process, so
	 * whichever test resolved it first would otherwise decide the answer for every later test.
	 *
	 * @return void
	 */
	private function reset_current_page(): void {
		$current_page = new \ReflectionProperty( PageController::class, 'current_page' );
		$current_page->setAccessible( true );
		$current_page->setValue( PageController::get_instance(), null );
	}

	/**
	 * Loads the saved note.
	 *
	 * @return Note
	 */
	private function get_saved_note(): Note {
		$note = Notes::get_note_by_name( ExtensionsWithoutSubscription::NOTE_NAME );
		$this->assertInstanceOf( Note::class, $note, 'The note should have been saved.' );

		return $note;
	}
}

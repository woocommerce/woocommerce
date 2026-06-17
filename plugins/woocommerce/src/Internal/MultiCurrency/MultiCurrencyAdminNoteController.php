<?php
/**
 * MultiCurrencyAdminNoteController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Internal\MultiCurrency\Providers\MultiCurrencyProviderAccountResolver;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyAdminNoteProjectionService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native multi-currency WC Admin notes when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyAdminNoteController implements RegisterHooksInterface {

	private const ADMIN_INIT_HOOK = 'admin_init';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * Provider account resolver.
	 *
	 * @var MultiCurrencyProviderAccountResolver
	 */
	private MultiCurrencyProviderAccountResolver $account_resolver;

	/**
	 * Admin request resolver.
	 *
	 * @var callable|null
	 */
	private $admin_request_resolver = null;

	/**
	 * Ajax request resolver.
	 *
	 * @var callable|null
	 */
	private $ajax_request_resolver = null;

	/**
	 * WooCommerce version resolver.
	 *
	 * @var callable|null
	 */
	private $wc_version_resolver = null;

	/**
	 * Provider connected resolver.
	 *
	 * @var callable|null
	 */
	private $provider_connected_resolver = null;

	/**
	 * Note can-be-added resolver.
	 *
	 * @var callable|null
	 */
	private $note_can_be_added_resolver = null;

	/**
	 * Note saver.
	 *
	 * @var callable|null
	 */
	private $note_saver = null;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter          $arbiter          Runtime owner arbiter.
	 * @param MultiCurrencyProviderAccountResolver $account_resolver Provider account resolver.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter, MultiCurrencyProviderAccountResolver $account_resolver ): void {
		$this->arbiter          = $arbiter;
		$this->account_resolver = $account_resolver;
	}

	/**
	 * Set the admin request resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $admin_request_resolver Resolver returning whether the request is admin.
	 */
	public function set_admin_request_resolver( callable $admin_request_resolver ): void {
		$this->admin_request_resolver = $admin_request_resolver;
	}

	/**
	 * Set the Ajax request resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $ajax_request_resolver Resolver returning whether the request is Ajax.
	 */
	public function set_ajax_request_resolver( callable $ajax_request_resolver ): void {
		$this->ajax_request_resolver = $ajax_request_resolver;
	}

	/**
	 * Set the WooCommerce version resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $wc_version_resolver Resolver returning the WooCommerce version.
	 */
	public function set_wc_version_resolver( callable $wc_version_resolver ): void {
		$this->wc_version_resolver = $wc_version_resolver;
	}

	/**
	 * Set the provider connected resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $provider_connected_resolver Resolver returning whether the provider account is connected.
	 */
	public function set_provider_connected_resolver( callable $provider_connected_resolver ): void {
		$this->provider_connected_resolver = $provider_connected_resolver;
	}

	/**
	 * Set the note can-be-added resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $note_can_be_added_resolver Resolver returning whether the note can be added.
	 */
	public function set_note_can_be_added_resolver( callable $note_can_be_added_resolver ): void {
		$this->note_can_be_added_resolver = $note_can_be_added_resolver;
	}

	/**
	 * Set the note saver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $note_saver Note saver accepting a projected note manifest.
	 */
	public function set_note_saver( callable $note_saver ): void {
		$this->note_saver = $note_saver;
	}

	/**
	 * Register admin note hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() || ! $this->is_admin_request() ) {
			return;
		}

		$this->add_action_once( self::ADMIN_INIT_HOOK, array( $this, 'handle_admin_init' ) );
	}

	/**
	 * Handle WC Admin note creation.
	 *
	 * @internal
	 */
	public function handle_admin_init(): void {
		$add_note_manifest = MultiCurrencyAdminNoteProjectionService::get_add_note_manifest(
			$this->is_ajax_request(),
			$this->get_wc_version(),
			$this->is_provider_connected(),
			$this->can_note_be_added()
		);

		if ( ! $add_note_manifest['should_add'] || ! is_array( $add_note_manifest['note'] ) ) {
			return;
		}

		$this->save_note( $add_note_manifest['note'] );
	}

	/**
	 * Tell whether the request is an admin request.
	 *
	 * @return bool
	 */
	private function is_admin_request(): bool {
		if ( null !== $this->admin_request_resolver ) {
			return (bool) call_user_func( $this->admin_request_resolver );
		}

		return is_admin();
	}

	/**
	 * Tell whether the request is an Ajax request.
	 *
	 * @return bool
	 */
	private function is_ajax_request(): bool {
		if ( null !== $this->ajax_request_resolver ) {
			return (bool) call_user_func( $this->ajax_request_resolver );
		}

		return wp_doing_ajax();
	}

	/**
	 * Get the WooCommerce version.
	 *
	 * @return string
	 */
	private function get_wc_version(): string {
		if ( null !== $this->wc_version_resolver ) {
			return (string) call_user_func( $this->wc_version_resolver );
		}

		return defined( 'WC_VERSION' ) ? (string) WC_VERSION : '';
	}

	/**
	 * Tell whether the provider account is connected.
	 *
	 * @return bool
	 */
	private function is_provider_connected(): bool {
		if ( null !== $this->provider_connected_resolver ) {
			return (bool) call_user_func( $this->provider_connected_resolver );
		}

		return $this->account_resolver->is_provider_connected();
	}

	/**
	 * Tell whether the note can be added.
	 *
	 * @return bool
	 */
	private function can_note_be_added(): bool {
		if ( null !== $this->note_can_be_added_resolver ) {
			return (bool) call_user_func( $this->note_can_be_added_resolver );
		}

		$note_manifest = MultiCurrencyAdminNoteProjectionService::get_note_manifest();

		try {
			return false === Notes::get_note_by_name( (string) $note_manifest['name'] );
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Save a WC Admin note from a projected manifest.
	 *
	 * @param array<string,mixed> $note_manifest Projected note manifest.
	 */
	private function save_note( array $note_manifest ): void {
		if ( null !== $this->note_saver ) {
			call_user_func( $this->note_saver, $note_manifest );
			return;
		}

		$note = new Note();
		$note->set_name( (string) $note_manifest['name'] );
		$note->set_title( (string) $note_manifest['title'] );
		$note->set_content( (string) $note_manifest['content'] );
		$note->set_content_data( (object) ( $note_manifest['content_data'] ?? array() ) );
		$note->set_type( (string) $note_manifest['type'] );
		$note->set_source( (string) $note_manifest['source'] );

		foreach ( (array) ( $note_manifest['actions'] ?? array() ) as $action ) {
			if ( ! is_array( $action ) ) {
				continue;
			}

			$note->add_action(
				(string) ( $action['name'] ?? '' ),
				(string) ( $action['label'] ?? '' ),
				(string) ( $action['query'] ?? '' ),
				(string) ( $action['status'] ?? Note::E_WC_ADMIN_NOTE_ACTIONED ),
				(bool) ( $action['primary'] ?? false )
			);
		}

		$note->save();
	}

	/**
	 * Register an action only once for this controller instance.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Hook callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 */
	private function add_action_once( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		if ( false === has_action( $hook, $callback ) ) {
			add_action( $hook, $callback, $priority, $accepted_args );
		}
	}
}

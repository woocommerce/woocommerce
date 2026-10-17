<?php
/**
 * Handles polling and storage of specs
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;
use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\GetRuleProcessor;
use WC_Helper;

/**
 * Specs data source poller class.
 * This handles polling specs from JSON endpoints, and
 * stores the specs in to the database as an option.
 */
class RemoteInboxNotificationsDataSourcePoller extends DataSourcePoller {
	const ID = 'remote_inbox_notifications';

	/**
	 * Transient holding the spec slugs and fetch time of the latest complete read.
	 */
	private const PUBLISHED_SNAPSHOT_TRANSIENT = 'woocommerce_admin_remote_inbox_notifications_published_snapshot';

	/**
	 * Published identities from this read's canonical response, or null when it is not authoritative.
	 *
	 * @var string[]|null
	 */
	private $published_slugs = null;

	/**
	 * Default data sources array.
	 *
	 * @deprecated since 9.5.0. Use get_data_sources() instead.
	 */
	const DATA_SOURCES = array();

	/**
	 * Class instance.
	 *
	 * @var RemoteInboxNotificationsDataSourcePoller instance
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self(
				self::ID,
				self::get_data_sources(),
				array(
					'spec_key'         => 'slug',
					'transient_expiry' => DAY_IN_SECONDS,
				)
			);
		}
		return self::$instance;
	}

	/**
	 * Read the data sources and record the identities published by the canonical WooCommerce.com feed.
	 *
	 * Invalid specs with usable slugs still count as published. An inconclusive canonical response clears the snapshot.
	 *
	 * @since 11.3.0
	 *
	 * @return bool Whether any specs were read.
	 */
	public function read_specs_from_data_sources() {
		$fetched_at            = time();
		$this->published_slugs = null;

		$result = parent::read_specs_from_data_sources();

		if ( null !== $this->published_slugs ) {
			$this->save_published_snapshot( $fetched_at, $this->published_slugs );
		} else {
			delete_transient( self::PUBLISHED_SNAPSHOT_TRANSIENT );
		}

		return $result;
	}

	/**
	 * Get the published identities from the latest complete canonical response.
	 *
	 * An empty or failed response never produces a snapshot, because the feed cannot tell those apart.
	 *
	 * @internal
	 * @since 11.3.0
	 *
	 * @return array{fetched_at: int, slugs: string[]}|null The snapshot, or null when none is stored.
	 */
	public function get_published_snapshot(): ?array {
		$snapshot = get_transient( self::PUBLISHED_SNAPSHOT_TRANSIENT );

		if ( ! is_array( $snapshot ) || ! is_int( $snapshot['fetched_at'] ?? null ) || ! is_array( $snapshot['slugs'] ?? null ) ) {
			return null;
		}

		return array(
			'fetched_at' => $snapshot['fetched_at'],
			'slugs'      => array_map( 'strval', array_values( array_filter( $snapshot['slugs'], 'is_scalar' ) ) ),
		);
	}

	/**
	 * Merge runnable specs and track canonical publication identities independently of validation.
	 *
	 * @since 11.3.0
	 *
	 * @param array  $specs_to_merge_in The specs to merge in to $specs.
	 * @param array  $specs             The list of specs being merged into.
	 * @param string $url               The url of the feed being merged in.
	 * @return void
	 */
	protected function merge_specs( $specs_to_merge_in, &$specs, $url ) {
		parent::merge_specs( $specs_to_merge_in, $specs, $url );

		if ( ! in_array( $url, self::get_data_sources(), true ) ) {
			return;
		}

		$this->published_slugs = null;
		$slugs                 = array();
		foreach ( $specs_to_merge_in as $spec ) {
			if ( ! isset( $spec->slug ) || ! is_scalar( $spec->slug ) || '' === (string) $spec->slug ) {
				return;
			}
			$slug = (string) $spec->slug;
			if ( isset( $slugs[ $slug ] ) ) {
				return;
			}
			$slugs[ $slug ] = $slug;
		}
		if ( ! empty( $slugs ) ) {
			$this->published_slugs = array_values( $slugs );
		}
	}

	/**
	 * Store a published snapshot unless a newer one is already stored.
	 *
	 * @param int      $fetched_at When the read started.
	 * @param string[] $slugs      Spec slugs returned by the read.
	 */
	private function save_published_snapshot( int $fetched_at, array $slugs ): void {
		$stored = $this->get_published_snapshot();
		if ( null !== $stored && $stored['fetched_at'] > $fetched_at ) {
			return;
		}

		set_transient(
			self::PUBLISHED_SNAPSHOT_TRANSIENT,
			array(
				'fetched_at' => $fetched_at,
				'slugs'      => $slugs,
			),
			$this->args['transient_expiry']
		);
	}

	/**
	 * Validate the spec.
	 *
	 * @param object $spec The spec to validate.
	 * @param string $url  The url of the feed that provided the spec.
	 *
	 * @return bool The result of the validation.
	 */
	protected function validate_spec( $spec, $url ) {
		$logger         = self::get_logger();
		$logger_context = array( 'source' => $url );

		if ( ! isset( $spec->slug ) || ! is_scalar( $spec->slug ) || '' === (string) $spec->slug ) {
			$logger->error(
				'Spec is invalid because the slug is missing or invalid in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $spec, true ), $logger_context );

			return false;
		}

		if ( ! isset( $spec->status ) ) {
			$logger->error(
				'Spec is invalid because the status is missing in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $spec, true ), $logger_context );

			return false;
		}

		if ( ! isset( $spec->locales ) || ! is_array( $spec->locales ) ) {
			$logger->error(
				'Spec is invalid because the status is missing or empty in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $spec, true ), $logger_context );

			return false;
		}

		if ( null === SpecRunner::get_locale( $spec->locales ) ) {
			$logger->error(
				'Spec is invalid because the locale could not be retrieved in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $spec, true ), $logger_context );

			return false;
		}

		if ( ! isset( $spec->type ) ) {
			$logger->error(
				'Spec is invalid because the type is missing in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $spec, true ), $logger_context );

			return false;
		}

		if ( isset( $spec->actions ) && is_array( $spec->actions ) ) {
			foreach ( $spec->actions as $action ) {
				if ( ! $this->validate_action( $action, $url ) ) {
					$logger->error(
						'Spec is invalid because an action is invalid in feed',
						$logger_context
					);
					// phpcs:ignore
					$logger->error( print_r( $spec, true ), $logger_context );

					return false;
				}
			}
		}

		if ( isset( $spec->rules ) && is_array( $spec->rules ) ) {
			foreach ( $spec->rules as $rule ) {
				if ( ! isset( $rule->type ) ) {
					$logger->error(
						'Spec is invalid because a rule type is empty in feed',
						$logger_context
					);
					// phpcs:ignore
					$logger->error( print_r( $rule, true ), $logger_context );
					// phpcs:ignore
					$logger->error( print_r( $spec, true ), $logger_context );

					return false;
				}

				$processor = GetRuleProcessor::get_processor( $rule->type );

				if ( ! $processor->validate( $rule ) ) {
					$logger->error(
						'Spec is invalid because a rule is invalid in feed',
						$logger_context
					);
					// phpcs:ignore
					$logger->error( print_r( $rule, true ), $logger_context );
					// phpcs:ignore
					$logger->error( print_r( $spec, true ), $logger_context );

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Validate the action.
	 *
	 * @param object $action The action to validate.
	 * @param string $url    The url of the feed containing the action (for error reporting).
	 *
	 * @return bool The result of the validation.
	 */
	private function validate_action( $action, $url ) {
		$logger         = self::get_logger();
		$logger_context = array( 'source' => $url );

		if ( ! isset( $action->locales ) || ! is_array( $action->locales ) ) {
			$logger->error(
				'Action is invalid because it has empty or missing locales in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $action, true ), $logger_context );

			return false;
		}

		if ( null === SpecRunner::get_action_locale( $action->locales ) ) {
			$logger->error(
				'Action is invalid because the locale could not be retrieved in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $action, true ), $logger_context );

			return false;
		}

		if ( ! isset( $action->name ) ) {
			$logger->error(
				'Action is invalid because the name is missing in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $action, true ), $logger_context );

			return false;
		}

		if ( ! isset( $action->status ) ) {
			$logger->error(
				'Action is invalid because the status is missing in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $action, true ), $logger_context );

			return false;
		}

		return true;
	}

	/**
	 * Get data sources.
	 *
	 * @return array
	 */
	public static function get_data_sources() {
		return array(
			WC_Helper::get_woocommerce_com_base_url() . 'wp-json/wccom/inbox-notifications/2.0/notifications.json',
		);
	}
}

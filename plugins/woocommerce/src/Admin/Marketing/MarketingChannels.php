<?php
/**
 * Handles the registration of marketing channels and acts as their repository.
 */

namespace Automattic\WooCommerce\Admin\Marketing;

use Automattic\WooCommerce\Internal\Admin\Marketing\MarketingSpecs;

/**
 * MarketingChannels repository class
 *
 * @since x.x.x
 */
class MarketingChannels {
	/**
	 * The registered marketing channels.
	 *
	 * @var MarketingChannelInterface[]
	 */
	private $registered_channels = [];

	/**
	 * Array of plugin slugs for allowed marketing channels.
	 *
	 * @var string[]
	 */
	private $allowed_channels;

	/**
	 * MarketingSpecs repository
	 *
	 * @var MarketingSpecs
	 */
	protected $marketing_specs;

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @param MarketingSpecs $marketing_specs The MarketingSpecs class.
	 *
	 * @internal
	 */
	final public function init( MarketingSpecs $marketing_specs ) {
		$this->marketing_specs  = $marketing_specs;
		$this->allowed_channels = $this->get_allowed_channels();
	}

	/**
	 * Registers a marketing channel.
	 *
	 * Note that only a predetermined list of third party extensions can be registered as a marketing channel.
	 *
	 * @param MarketingChannelInterface $channel The marketing channel to register.
	 *
	 * @return void
	 *
	 * @see MarketingChannels::is_channel_allowed() Checks if the marketing channel is allowed to be registered or not.
	 */
	public function register( MarketingChannelInterface $channel ): void {
		if ( ! $this->is_channel_allowed( $channel ) ) {
			// Silently log an error and bail.
			wc_get_logger()->error( sprintf( 'Marketing channel %s (%s) cannot be registered!', $channel->get_name(), $channel->get_slug() ) );

			return;
		}

		$this->registered_channels[ $channel->get_slug() ] = $channel;
	}

	/**
	 * Returns an array of all registered marketing channels.
	 *
	 * @return MarketingChannelInterface[]
	 */
	public function get_registered_channels(): array {
		/**
		 * Filter the list of registered marketing channels.
		 *
		 * Note that only a predetermined list of third party extensions can be registered as a marketing channel.
		 * Any new plugins added to this array will be cross-checked with that list, which is obtained from WooCommerce.com API.
		 *
		 * @param MarketingChannelInterface[] $channels Array of registered marketing channels.
		 *
		 * @since x.x.x
		 */
		$channels = apply_filters( 'woocommerce_marketing_channels', $this->registered_channels );

		// Only return allowed channels.
		$allowed_channels = array_filter(
			$channels,
			function ( MarketingChannelInterface $channel ) {
				if ( ! $this->is_channel_allowed( $channel ) ) {
					// Silently log an error and bail.
					wc_get_logger()->error( sprintf( 'Marketing channel %s (%s) cannot be registered!', $channel->get_name(), $channel->get_slug() ) );

					return false;
				}

				return true;
			}
		);

		return array_values( $allowed_channels );
	}

	/**
	 * Returns an array of plugin slugs for the marketing channels that are allowed to be registered.
	 *
	 * @return array
	 */
	protected function get_allowed_channels(): array {
		$recommended_channels = $this->marketing_specs->get_recommended_plugins();
		if ( empty( $recommended_channels ) ) {
			return [];
		}

		return array_column( $recommended_channels, 'product', 'product' );
	}

	/**
	 * Determines whether the given marketing channel is allowed to be registered.
	 *
	 * @param MarketingChannelInterface $channel The marketing channel object.
	 *
	 * @return bool
	 */
	protected function is_channel_allowed( MarketingChannelInterface $channel ): bool {
		return isset( $this->allowed_channels[ $channel->get_slug() ] );
	}
}

<?php
/**
 * InstalledExtensions class file.
 */

namespace Automattic\WooCommerce\Admin\Marketing;

/**
 * Installed Marketing Extensions class.
 */
class InstalledExtensions {
	/**
	 * MarketingChannels repository
	 *
	 * @var MarketingChannels
	 */
	protected $marketing_channels;

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @param MarketingChannels $marketing_channels The MarketingChannels repository.
	 *
	 * @internal
	 */
	final public function init( MarketingChannels $marketing_channels ) {
		$this->marketing_channels = $marketing_channels;
	}

	/**
	 * Gets an array of plugin data for the "Installed marketing extensions" card.
	 */
	public function get_data(): array {
		return array_map(
			function ( MarketingChannelInterface $channel ) {
				return [
					'slug'                    => $channel->get_slug(),
					'status'                  => $channel->is_setup_completed() ? 'configured' : 'activated',
					'settingsUrl'             => $channel->get_setup_url(),
					'name'                    => $channel->get_name(),
					'description'             => $channel->get_description(),
					'product_listings_status' => $channel->get_product_listings_status(),
					'errors_count'            => $channel->get_errors_count(),
					'icon'                    => $channel->get_icon_url(),
				];
			},
			$this->marketing_channels->get_registered_channels()
		);
	}
}

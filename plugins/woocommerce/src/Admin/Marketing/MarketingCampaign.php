<?php
/**
 * Represents a marketing/ads campaign for marketing channels.
 *
 * Marketing channels (implementing MarketingChannelInterface) can use this class to map their campaign data and present it to WooCommerce core.
 */

namespace Automattic\WooCommerce\Admin\Marketing;

use JsonSerializable;

/**
 * MarketingCampaign class
 *
 * @since x.x.x
 */
class MarketingCampaign implements JsonSerializable {
	/**
	 * The unique identifier.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * The marketing channel that this campaign belongs to.
	 *
	 * @var MarketingChannelInterface
	 */
	protected $channel;

	/**
	 * Title of the marketing campaign.
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * The URL to the channel's campaign management page.
	 *
	 * @var string
	 */
	protected $manage_url;

	/**
	 * The cost of the marketing campaign with the currency.
	 *
	 * @var Price
	 */
	protected $cost;

	/**
	 * MarketingCampaign constructor.
	 *
	 * @param string                    $id         The marketing campaign's unique identifier.
	 * @param MarketingChannelInterface $channel    The marketing channel that this campaign belongs to.
	 * @param string                    $title      The title of the marketing campaign.
	 * @param string                    $manage_url The URL to the channel's campaign management page.
	 * @param Price|null                $cost       The cost of the marketing campaign with the currency.
	 */
	public function __construct( string $id, MarketingChannelInterface $channel, string $title, string $manage_url, Price $cost = null ) {
		$this->id         = $id;
		$this->channel    = $channel;
		$this->title      = $title;
		$this->manage_url = $manage_url;
		$this->cost       = $cost;
	}

	/**
	 * Returns the marketing campaign's unique identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Returns the marketing channel that this campaign belongs to.
	 *
	 * @return MarketingChannelInterface
	 */
	public function get_channel(): MarketingChannelInterface {
		return $this->channel;
	}

	/**
	 * Returns the title of the marketing campaign.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Returns the URL to manage the marketing campaign.
	 *
	 * @return string
	 */
	public function get_manage_url(): string {
		return $this->manage_url;
	}

	/**
	 * Returns the cost of the marketing campaign with the currency.
	 *
	 * @return Price|null
	 */
	public function get_cost(): ?Price {
		return $this->cost;
	}

	/**
	 * Serialize the marketing campaign data.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'id'         => $this->get_id(),
			'channel'    => $this->get_channel()->get_slug(),
			'title'      => $this->get_title(),
			'manage_url' => $this->get_manage_url(),
			'cost'       => $this->get_cost(),
		];
	}
}

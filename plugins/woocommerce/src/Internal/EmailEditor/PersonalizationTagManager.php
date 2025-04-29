<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags\CustomerTagsProvider;
use Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags\OrderTagsProvider;
use Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags\SiteTagsProvider;
use Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags\StoreTagsProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Manages personalization tags for WooCommerce emails.
 *
 * @internal
 */
class PersonalizationTagManager {

	/**
	 * The customer related tags provider.
	 *
	 * @var CustomerTagsProvider
	 */
	private $customer_tags_provider;

	/**
	 * The order related tags provider.
	 *
	 * @var OrderTagsProvider
	 */
	private $order_tags_provider;

	/**
	 * The site related tags provider.
	 *
	 * @var SiteTagsProvider
	 */
	private $site_tags_provider;

	/**
	 * The store related tags provider.
	 *
	 * @var StoreTagsProvider
	 */
	private $store_tags_provider;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->customer_tags_provider = new CustomerTagsProvider();
		$this->order_tags_provider    = new OrderTagsProvider();
		$this->site_tags_provider     = new SiteTagsProvider();
		$this->store_tags_provider    = new StoreTagsProvider();
	}

	/**
	 * Initialize the personalization tag manager.
	 *
	 * @internal
	 * @return void
	 */
	final public function init(): void {
		add_filter( 'woocommerce_email_editor_register_personalization_tags', array( $this, 'register_personalization_tags' ) );
	}

	/**
	 * Register WooCommerce personalization tags with the registry.
	 *
	 * @param Personalization_Tags_Registry $registry The personalization tags registry.
	 * @return Personalization_Tags_Registry
	 */
	public function register_personalization_tags( Personalization_Tags_Registry $registry ) {
		$this->customer_tags_provider->register_tags( $registry );
		$this->order_tags_provider->register_tags( $registry );
		$this->site_tags_provider->register_tags( $registry );
		$this->store_tags_provider->register_tags( $registry );

		return $registry;
	}
}

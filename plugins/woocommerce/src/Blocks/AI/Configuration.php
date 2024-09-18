<?php

namespace Automattic\WooCommerce\Blocks\AI;

use Automattic\Jetpack\Config;
use Automattic\Jetpack\Connection\Manager;
use Automattic\Jetpack\Connection\Utils;

/**
 * Class Configuration
 *
 * @internal
 */
class Configuration {

	/**
	 * The name of the option that stores the site owner's consent to connect to the AI API.
	 *
	 * @var string
	 */
	private $consent_option_name = 'woocommerce_blocks_allow_ai_connection';
	/**
	 * The Jetpack connection manager.
	 *
	 * @var Manager
	 */
	private $manager;
	/**
	 * The Jetpack configuration.
	 *
	 * @var Config
	 */
	private $config;

	/**
	 * Configuration constructor.
	 */
	public function __construct() {
		if ( ! class_exists( 'Automattic\Jetpack\Connection\Manager' ) || ! class_exists( 'Automattic\Jetpack\Config' ) ) {
			return;
		}

		$this->manager = new Manager( 'woocommerce_blocks' );
		$this->config  = new Config();
	}

	/**
	 * Initialize the site and user connection and registration.
	 *
	 * @return bool|\WP_Error
	 */
	public function init() {
		if ( ! $this->should_connect() ) {
			return false;
		}

		$this->enable_connection_feature();

		return $this->register_and_connect();
	}

	/**
	 * Verify if the site should connect to Jetpack.
	 *
	 * @return bool
	 */
	private function should_connect() {
		$site_owner_consent = get_option( $this->consent_option_name );

		return $site_owner_consent && class_exists( 'Automattic\Jetpack\Connection\Utils' ) && class_exists( 'Automattic\Jetpack\Connection\Manager' );
	}

	/**
	 * Initialize Jetpack's connection feature within the WooCommerce Blocks plugin.
	 *
	 * @return void
	 */
	private function enable_connection_feature() {
		$this->config->ensure(
			'connection',
			array(
				'slug' => 'woocommerce/woocommerce-blocks',
				'name' => 'WooCommerce Blocks',
			)
		);
	}

	/**
	 * Register the site with Jetpack.
	 *
	 * @return bool|\WP_Error
	 */
	private function register_and_connect() {
		Utils::init_default_constants();

		$jetpack_id     = \Jetpack_Options::get_option( 'id' );
		$jetpack_public = \Jetpack_Options::get_option( 'public' );

		$register = $jetpack_id && $jetpack_public ? true : $this->manager->register();

		if ( true === $register && ! $this->manager->is_user_connected() ) {
			$this->manager->connect_user();
			return true;
		}

		return false;
	}

	/**
	 * Unregister the site with Jetpack.
	 *
	 * @return void
	 */
	private function unregister_site() {
		if ( $this->manager->is_connected() ) {
			$this->manager->remove_connection();
		}
	}
}

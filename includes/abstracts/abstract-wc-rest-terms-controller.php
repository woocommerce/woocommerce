<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Rest Terms Controler Class
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/Abstracts
 * @version  2.6.0
 */
abstract class WC_REST_Terms_Controller extends WP_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '';
}

<?php
/**
 * WooCommerce Admin Input Parameter Exception Class
 *
 * Exception class thrown when user provides incorrect parameters.
 *
 * @package  WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\API\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Reports_Parameter_Exception class.
 */
class ParameterException extends \WC_Data_Exception {}

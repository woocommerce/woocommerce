<?php

defined( 'ABSPATH' ) || exit;

interface WC_WCCOM_Site_Installation_Step {
	public function __construct( $state );

	public function run() ;
}
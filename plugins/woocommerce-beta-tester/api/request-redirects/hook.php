<?php

defined( 'ABSPATH' ) || exit;

$redirectors = get_option( WCA_Test_Helper_Remote_Get_Redirector::OPTION_NAME, array() );

add_filter(
	'pre_http_request',
	function ( $preempt, $parsed_args, $url ) use ( $redirectors ) {
		foreach ( $redirectors as $redirector ) {
			if ( $url === $redirector['original_endpoint'] ) {
				if ( empty( $redirector['enabled'] ) ) {
					continue;
				}

				if ( ! empty( $redirector['username'] ) && ! empty( $redirector['password'] ) ) {
					$parsed_args['headers']['Authorization'] = 'Basic ' . base64_encode( $redirector['username'] . ':' . $redirector['password'] );
				}
				return wp_remote_get( $redirector['new_endpoint'], $parsed_args );
			}
		}
		return false;
	},
	10,
	3
);
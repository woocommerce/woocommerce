<?php

defined( 'ABSPATH' ) || exit;

$redirectors = get_option( WCA_Test_Helper_Remote_Get_Redirector::OPTION_NAME, array() );

add_filter(
	'pre_http_request',
	function ( $preempt, $parsed_args, $url ) use ( $redirectors ) {
		$logger = new WC_Logger;
		$log_entry = "URL: $url\n";

		foreach ( $redirectors as $redirector ) {
			if ( $url === $redirector['original_endpoint'] ) {
				if ( empty( $redirector['enabled'] ) ) {
					$log_entry .= "Redirector is disabled " . $redirector['new_endpoint'] . "\n";
					$log_entry .= print_r( $redirector, true );
					continue;
				}

				$log_entry .= "Matched {$redirector['original_endpoint']} to {$redirector['new_endpoint']}\n";


				if ( ! empty( $redirector['username'] ) && ! empty( $redirector['password'] ) ) {
					$parsed_args['headers']['Authorization'] = 'Basic ' . base64_encode( $redirector['username'] . ':' . $redirector['password'] );
				}
				$response =  wp_remote_get( $redirector['new_endpoint'], $parsed_args );
				$log_entry .= print_r( $response, true );
				return $response;
			} else {
				$log_entry .= "No match for {$url}, {$redirector['original_endpoint']}\n";
			}
		}
		$logger->add('test', $log_entry);
		return false;
	},
	10,
	3
);
/**
 * External dependencies
 */
import { HTTPClientFactory } from '@woocommerce/api';
import config from 'config';

// Prepare the HTTP client that will be consumed by the repository.
// This is necessary so that it can make requests to the REST API.
const admin = config.get< {
	username: string;
	password: string;
} >( 'users.admin' );
const url = config.get< string >( 'url' );

export const httpClient = HTTPClientFactory.build( url )
	.withBasicAuth( admin.username, admin.password )
	.create();

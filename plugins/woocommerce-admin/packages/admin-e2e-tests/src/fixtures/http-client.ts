/**
 * External dependencies
 */
import { HTTPClientFactory } from '@woocommerce/api';
/* eslint-disable @typescript-eslint/no-var-requires */
const config = require( 'config' );

// Prepare the HTTP client that will be consumed by the repository.
// This is necessary so that it can make requests to the REST API.
const admin = config.get( 'users.admin' );
const url = config.get( 'url' );

export const httpClient = HTTPClientFactory.build( url )
	.withBasicAuth( admin.username, admin.password )
	.create();

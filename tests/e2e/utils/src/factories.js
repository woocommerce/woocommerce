const config = require( 'config' );

import { HTTPClientFactory, SimpleProduct } from '@woocommerce/api';

const httpClient = HTTPClientFactory.withBasicAuth(
	config.get( 'url' ) + '/wp-json',
	config.get( 'users.admin.username' ),
	config.get( 'users.admin.password' ),
);

const factories = {
	products: {
		simple: SimpleProduct.factory( SimpleProduct.restRepository( httpClient ) ),
	},
};

export default factories;

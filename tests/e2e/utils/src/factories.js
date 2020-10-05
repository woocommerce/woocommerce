import { HTTPClientFactory } from '@woocommerce/api';
const config = require( 'config' );

const httpClient = HTTPClientFactory.withBasicAuth(
	config.get( 'url' ) + '/wp-json',
	config.get( 'users.admin.username' ),
	config.get( 'users.admin.password' ),
);

import { simpleProductFactory } from './factories/simple-product';

const factories = {
	products: {
		simple: simpleProductFactory( httpClient ),
	},
};

export default factories;

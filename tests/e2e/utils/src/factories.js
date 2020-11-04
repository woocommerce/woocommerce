import { HTTPClientFactory } from '@woocommerce/api';
const config = require( 'config' );

const httpClient = HTTPClientFactory.build( config.get( 'url' ) )
	.withBasicAuth( config.get( 'users.admin.username' ), config.get( 'users.admin.password' ) )
	.create();

import { simpleProductFactory } from './factories/simple-product';

const factories = {
	products: {
		simple: simpleProductFactory( httpClient ),
	},
};

export default factories;

import { HTTPClientFactory } from '@woocommerce/api';
const config = require( 'config' );
import { simpleProductFactory } from './factories/simple-product';

const apiUrl = config.get( 'url' );
const adminUsername = config.get( 'users.admin.username' );
const adminPassword = config.get( 'users.admin.password' );
const withDefaultPermalinks = HTTPClientFactory.build( apiUrl )
	.withBasicAuth( adminUsername, adminPassword )
	.create();
const withIndexPermalinks = HTTPClientFactory.build( apiUrl )
	.withBasicAuth( adminUsername, adminPassword )
	.withIndexPermalinks()
	.create();

const factories = {
	api: {
		withDefaultPermalinks,
		withIndexPermalinks,
	},
	products: {
		simple: simpleProductFactory( withDefaultPermalinks ),
	},
};

export default factories;

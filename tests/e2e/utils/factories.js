import {
	AdapterTypes,
	initializeUsingBasicAuth,
	ModelRegistry,
	registerSimpleProduct
} from '@woocommerce/api';

const config = require( 'config' );

const modelRegistry = new ModelRegistry()

// Register all of the different factories that we're going to need.
registerSimpleProduct( modelRegistry );

// Make sure to perform the initialization AFTER registering all of the factories, otherwise the adapters might be
// missed on subsequent registrations.
initializeUsingBasicAuth( modelRegistry,
	config.get( 'url' ) + '/wp-json',
	config.get( 'users.admin.username' ),
	config.get( 'users.admin.password' )
);
modelRegistry.changeAllFactoryAdapters( AdapterTypes.API );

export default modelRegistry;

# Model Factories

A simple interface for generating models of different types.

## Installation

``bash
npm install @woocommerce/model-factories --save-dev
``

## Usage

Consumers of this package should rely on an instance of `ModelRegistry` to access the factories.
Here is an example of how to initialize and use the package to generate a simple product:

```javascript
import { 
    AdapterTypes,
    initializeUsingBasicAuth,
    ModelRegistry,
    registerSimpleProduct,
    SimpleProduct
} from '@woocommerce/model-factories';

// The ModelRegistry instance is where all of the factories and adapters are stored in an easy-to-access way.
const modelRegistry = new ModelRegistry()

// Call the register functions to add a kind of factory to the model registry.
// This will also add any adapters we've created for the factory, allowing it
// to be created on the server.
registerSimpleProduct( modelRegistry );

// Before you can use the included API adapter you need to initialize it using one of the utility methods.
// If you do not initialize the API adapters they will not be able to make requests to the API.
// Note that these utility functions only set up adapters that have been registered already
// and so further calls to `registeryXXX` functions will have adapters that aren't ready.
initializeUsingBasicAuth( modelRegistry, 'https://test.test/wp-json', 'admin', 'password' );
initializeUsingOAuth( modelRegistry, 'https://test.test/wp-json', 'consumer_key', 'consumer_secret' );

// In order to actually create the models on the server, each registered factory must have an adapter set.
// You can do this on a per-factory basis using
modelRegistry.changeFactoryAdapter( SimpleProduct, AdapterTypes.API );
// You can do this to all factories registered using
modelRegistry.changeAllFactoryAdapters( AdapterTypes.API );

// Once all of the initialization has been taken care of you can create models!
// Any fields that are not defined will be filled out by random data.
const product = await modelRegistry.getFactory( SimpleProduct ).create( { name: 'Test Product' } );
// You can now access the ID of the created model using `product.id`!

// You can also create models in bulk!
const poducts = await modelRegistry.getFactory( SimpleProduct ).createList( 5 );
// You now have an array of products to work with!
```

## Custom Models

## Custom Adapters

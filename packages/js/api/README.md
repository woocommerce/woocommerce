# WooCommerce API Client

An API client for interacting with WooCommerce installations that works both in the browser and in Node environments. Here are the current and planned
features:

- [x] TypeScript Definitions \*
- [x] Axios API Client with support for OAuth & basic auth
- [X] Partial support to Repositories, to simplify interaction with basic data types \*
- [x] Service classes for common activities such as changing settings

_\* TypeScript Definitions and Repositories are currently only supported for [Products](https://woocommerce.github.io/woocommerce-rest-api-docs/#products), and partially supported for [Orders](https://woocommerce.github.io/woocommerce-rest-api-docs/#orders)._

## Differences from @woocommerce/woocommerce-rest-api

WooCommerce has two API clients in JavaScript for interacting with a WooCommerce installation's RESTful API. This package, and the [@woocommerce/woocommerce-rest-api](https://www.npmjs.com/package/@woocommerce/woocommerce-rest-api) package.

The main difference between them is the Repositories and the TypeScript definitions for the supported endpoints. When using Axios directly, as you can do with both libraries, you query the WooCommerce API in a raw object format, following the [API documentation](https://woocommerce.github.io/woocommerce-rest-api-docs/#introduction) parameters. Comparatively, with the Repositories provided in this package, you have the parameters as properties of an object, which gives you the benefits of auto-complete and strict types, for instance.

## Usage

```bash
npm install @woocommerce/api --save-dev
```

Depending on what you're intending to get out of the API client there are a few different ways of using it.

### REST API

The simplest way to use the client is directly:

```javascript
import { HTTPClientFactory } from '@woocommerce/api';

// You can create an API client using the client factory with pre-configured middleware for convenience.
let client = HTTPClientFactory.build( 'https://example.com' )
    .withBasicAuth( 'username', 'password' )
    .create();

// You can also create an API client configured for requests using OAuth.
client = HTTPClientFactory.build( 'https://example.com' )
    .withOAuth( 'consumer_secret', 'consumer_password' )
    .create();

// You can then use the client to make API requests.
client.get( '/wc/v3/products' ).then( ( response ) => {
  // Access the status code from the response.
  response.status;
  // Access the headers from the response.
  response.headers;
  // Access the data from the response, in this case, the products.
  response.data;
}, ( error ) => {
  // Handle errors that may have come up.
} );

```

### Repositories

As a convenience utility we've created repositories for core data types that can simplify interacting with the API:

#### Parent/Base Repositories

- `SimpleProduct`
- `ExternalProduct`
- `GroupedProduct`
- `VariableProduct`
- `Coupon`
- `Order`
- `SettingsGroup`

#### Child Repositories

- `ProductVariation`
- `Setting`

These repositories provide CRUD methods for ease-of-use:

```javascript
import { HTTPClientFactory, SimpleProduct } from '@woocommerce/api';

// Prepare the HTTP client that will be consumed by the repository.
// This is necessary so that it can make requests to the REST API.
const httpClient = HTTPClientFactory.build( 'https://example.com' )
    .withBasicAuth( 'username', 'password' )
    .create();

const repository = SimpleProduct.restRepository( httpClient );

// The repository can now be used to create models.
const product = repository.create( { name: 'Simple Product', regularPrice: '9.99' } );

// The response will be one of the models with structured properties and TypeScript support.
product.id;
```

#### Repository Methods

The following methods are available on all repositories if the corresponding method is available on the API endpoint:

- `create( {...properties} )` - Create a single object of the model type
- `delete( objectId )` - Delete a single object of the model type
- `list( {...parameters} )` - Retrieve a list of the existing objects of that model type
- `read( objectId )` - Read a single object of the model type
- `update( objectId, {...properties} )` - Update a single object of the model type

#### Child Repositories Use

In child model repositories, each method requires the `parentId` as the first parameter:

```javascript
import { HTTPClientFactory, VariableProduct, ProductVariation } from '@woocommerce/api';

const httpClient = HTTPClientFactory.build( 'https://example.com' )
    .withBasicAuth( 'username', 'password' )
    .withIndexPermalinks()
    .create();

const productRepository = VariableProduct.restRepository( httpClient );
const variationRepository = ProductVariation.restRepository( httpClient );

const product = await productRepository.create({
    "name": "Variable Product with Three Attributes",
    "defaultAttributes": [
    {
     "id": 0,
     "name": "Size",
     "option": "Medium"
    },
    {
     "id": 0,
     "name": "Colour",
     "option": "Blue"
    }
    ],
    "attributes": [
    {
     "id": 0,
     "name": "Colour",
     "isVisibleOnProductPage": true,
     "isForVariations": true,
     "options": [
       "Red",
       "Green",
       "Blue"
     ],
     "sortOrder": 0
    },
    {
     "id": 0,
     "name": "Size",
     "isVisibleOnProductPage": true,
     "isForVariations": true,
     "options": [
       "Small",
       "Medium",
       "Large"
     ],
     "sortOrder": 0
   }
  ]
});

const variation = await variationRepository.create( product.id, {
    "regularPrice": "19.99",
    "attributes": [
      {
        "name": "Size",
        "option": "Large"
      },
      {
        "name": "Colour",
        "option": "Red"
      }
    ]
});
```

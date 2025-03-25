# CRUD Data Stores

The CRUD data store is a set of utilities to allow faster and less error prone creation of data stores that have create, read, update, and delete capabilities.

## Usage

The CRUD data store methods can be used in one of a couple ways.

### Default data store

If the default CRUD actions work well for your use case, you can use the quicker, more opinionated setup.

```js
import { createCrudDataStore } from '../crud';

createCrudDataStore( {
	storeName: 'my/custom/store',
	resourceName: 'MyThing',
	pluralResourceName: 'MyThings',
	namespace: '/my/rest/namespace',
    storeConfig: {
        actions: additionalActions,
        selectors: additionalSelectors,
        resolvers: additionalResolvers,
        controls: additionalControls,
    }
} );
```

#### TypeScript

For TypeScript support, you can define your types and pass them as generic parameters to `createCrudDataStore`:

```ts
// types.ts
import { CrudActions, CrudSelectors } from '../crud/types';

// Define your resource type
export type MyThing = {
    id: number;
    name: string;
    description: string;
};

// Define the query parameters for mutations
export type QueryMyThing = {
    name: string;
    description: string;
};

// Define which properties are read-only vs mutable
type ReadOnlyProperties = 'id';
type MutableProperties = Partial<Omit<QueryMyThing, ReadOnlyProperties>>;

// Define your query parameters for selectors
type Query = {
    context?: string;
    order_by?: string;
};

// Create your typed actions and selectors
export type MyThingActions = CrudActions<
    'MyThing',
    MyThing,
    MutableProperties
>;

export type MyThingSelectors = CrudSelectors<
    'MyThing',
    'MyThings',
    MyThing,
    Query,
    MutableProperties
>;

// index.ts
import { createCrudDataStore } from '../crud';
import { MyThingActions, MyThingSelectors } from './types';

export const store = createCrudDataStore<MyThingActions, MyThingSelectors>({
    storeName: 'my/custom/store',
    resourceName: 'MyThing',
    pluralResourceName: 'MyThings',
    namespace: '/my/rest/namespace',
});
```

This will register a data store named `my/custom/store` with the following type-safe selectors:

| Selector | Description |
| --- | --- |
| `getMyThing( id )` | Get an item by ID |
| `getMyThingError( id )` | Get the error for an item. |
| `getMyThings( query = {} )` | Get all items, optionally by a specific query. |
| `getMyThingsError( query = {} )` | Get the error for a set of items by query. |

Example usage: `wp.data.select('my/custom/store').getMyThing( 3 );`

The following resolvers will be added:

| Resolver | Method | Endpoint |
| --- | --- | --- |
| `getMyThing( id )` | GET | `<namespace>/<id>` |
| `getMyThings( query = {} )` | GET | `<namespace>` |

The following actions are available for dispatch on the created data store:

| Resolver | Method | Endpoint |
| --- | --- | --- |
| `createMyThing( query )` | POST | `<namespace>` |
| `deleteMyThing( id, force = true )` | DELETE | `<namespace>/<id>` |
| `updatetMyThing( id, query )` | PUT | `<namespace>/<id>` |

Example usage: `wp.data.dispatch('my/custom/store').updateMyThing( 3, { name: 'New name' } );`

### Customized data store

If the default settings are not adequate for your needs, you can always create your own data store and supplement the default CRUD actions with your own.

```js
import { createSelectors } from '../crud/selectors';
import { createResolvers } from '../crud/resolvers';
import { createActions } from '../crud/actions';
import { registerStore, combineReducers } from '@wordpress/data';

const dataStoreArgs = {
    resourceName: 'MyThing',
    pluralResourceName: 'MyThings',
}

const crudActions = createActions( dataStoreArgs )
const crudSelectors = createSelectors( dataStoreArgs )
const crudResolvers = createResolvers( { ...dataStoreArgs, namespace: 'my/rest/namespace' } )

registerStore( 'my/custom/store', {
	reducer: combineReducers( { reducer, myReducer } ),
	actions: { ...crudActions, myActions },
	controls,
	selectors: { ...crudSelectors, mySelectors },
	resolvers: { ...crudResolvers, myResolvers },
} );
```

#### TypeScript

For TypeScript support in a customized store, you can define your types and extend the base CRUD types:

```ts
// types.ts
import { CrudActions, CrudSelectors } from '../crud/types';
import { DispatchFromMap } from '@automattic/data-stores';

// Define your resource type
export interface MyCustomResource {
    id: number;
    name: string;
    // ... other properties
}

// Define which properties are read-only
type ReadOnlyProperties = 'id' | 'date_created';

// Define mutable properties
type MutableProperties = Partial<Omit<MyCustomResource, ReadOnlyProperties>>;

// Define query parameters
type Query = {
    context?: string;
    custom_filter?: string;
};

// Define any custom actions
export interface CustomActions {
    customAction( id: number ): void;
}

// Define any custom selectors
export interface CustomSelectors {
    getCustomData( id: number ): MyCustomResource | undefined;
}

// Combine CRUD and custom types
export type MyCustomActions = CrudActions<
    'MyCustomResource',
    MyCustomResource,
    MutableProperties
> & CustomActions;

export type MyCustomSelectors = CrudSelectors<
    'MyCustomResource',
    'MyCustomResources',
    MyCustomResource,
    Query,
    MutableProperties
> & CustomSelectors;



// index.ts
import { Reducer } from 'redux';
import { createCrudDataStore } from '../crud';
import { ResourceState } from '../crud/reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import { reducer } from './reducer';
import { MyCustomActions, MyCustomSelectors } from './types';

export const store = createCrudDataStore<MyCustomActions, MyCustomSelectors>({
    storeName: 'my/custom/store',
    resourceName: 'MyCustomResource',
    pluralResourceName: 'MyCustomResources',
    namespace: '/my/rest/namespace',
    storeConfig: {
        reducer: reducer as Reducer<ResourceState>,
        actions: actions as MyCustomActions,
        selectors: selectors as MyCustomSelectors,
    },
});
```

This TypeScript implementation provides type safety for your actions and selectors while allowing you to extend the base CRUD functionality with custom methods.

## Structure

The data store schema is set in such a way that allows queries to be cached and previously downloaded resources to be more readily available.

```js
{
    items: {
        21: { ... },
    },
    errors: {
        'GET_ITEMS:page=3': 'There was an error trying to fetch page 3',
    },
    data: {
        'GET_ITEMS:page=2' : [ 21 ],
    }
}
```

By default, the CRUD data store expects a property of `id` to be present on all resources.

import { HTTPClient } from '../../http';
import {
	ListFn,
	ModelRepositoryParams,
	ModelClass,
	HasParent,
	ParentID,
	ListChildFn,
	ReadChildFn,
	ReadFn,
	DeleteFn,
	UpdateFn,
	UpdateChildFn,
	DeleteChildFn,
	CreateFn,
	ModelTransformer,
	IgnorePropertyTransformation,
	// @ts-ignore
	ModelParentID,
} from '../../framework';
import {
	ModelID,
	MetaData,
	ModelConstructor,
} from '../../models';

/**
 * Creates a new transformer for metadata models.
 *
 * @return {ModelTransformer} The created transformer.
 */
export function createMetaDataTransformer(): ModelTransformer< MetaData > {
	return new ModelTransformer(
		[
			new IgnorePropertyTransformation( [ 'id' ] ),
		],
	);
}

/**
 * A callback to build a URL for a request.
 *
 * @callback BuildURLFn
 * @param {ModelID} [id] The ID of the model we're dealing with if used for the request.
 * @return {string} The URL to make the request to.
 */
type BuildURLFn< T extends ( 'list' | 'general' ) = 'general' > = [ T ] extends [ 'list' ] ? () => string : ( id: ModelID ) => string;

/**
 * A callback to build a URL for a request.
 *
 * @callback BuildURLWithParentFn
 * @param {P} parent The ID of the model's parent.
 * @param {ModelID} [id] The ID of the model we're dealing with if used for the request.
 * @return {string} The URL to make the request to.
 * @template {ModelParentID} P
 */
type BuildURLWithParentFn< P extends ModelRepositoryParams, T extends ( 'list' | 'general' ) = 'general' > = [ T ] extends [ 'list' ]
	? ( parent: ParentID< P > ) => string
	: ( parent: ParentID< P >, id: ModelID ) => string;

/**
 * Creates a callback for listing models using the REST API.
 *
 * @param {BuildURLFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {ListFn} The callback for the repository.
 */
export function restList< T extends ModelRepositoryParams >(
	buildURL: HasParent< T, never, BuildURLFn< 'list' > >,
	modelClass: ModelConstructor< ModelClass< T > >,
	httpClient: HTTPClient,
	transformer: ModelTransformer< ModelClass< T > >,
): ListFn< T > {
	return async ( params ) => {
		const response = await httpClient.get( buildURL(), params );

		const list: ModelClass< T >[] = [];
		for ( const raw of response.data ) {
			list.push( transformer.toModel( modelClass, raw ) );
		}

		return Promise.resolve( list );
	};
}

/**
 * Creates a callback for listing child models using the REST API.
 *
 * @param {BuildURLWithParentFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {ListChildFn} The callback for the repository.
 */
export function restListChild< T extends ModelRepositoryParams >(
	buildURL: HasParent< T, BuildURLWithParentFn< T, 'list' >, never >,
	modelClass: ModelConstructor< ModelClass< T > >,
	httpClient: HTTPClient,
	transformer: ModelTransformer< ModelClass< T > >,
): ListChildFn< T > {
	return async ( parent, params ) => {
		const response = await httpClient.get( buildURL( parent ), params );

		const list: ModelClass< T >[] = [];
		for ( const raw of response.data ) {
			list.push( transformer.toModel( modelClass, raw ) );
		}

		return Promise.resolve( list );
	};
}

/**
 * Creates a callback for creating models using the REST API.
 *
 * @param {Function} buildURL A callback to build the URL. (This is passed the properties for the new model.)
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {CreateFn} The callback for the repository.
 */
export function restCreate< T extends ModelRepositoryParams >(
	buildURL: ( properties: Partial< ModelClass< T > > ) => string,
	modelClass: ModelConstructor< ModelClass< T > >,
	httpClient: HTTPClient,
	transformer: ModelTransformer< ModelClass< T > >,
): CreateFn< T > {
	return async ( properties ) => {
		const response = await httpClient.post(
			buildURL( properties ),
			transformer.fromModel( properties ),
		);

		return Promise.resolve( transformer.toModel( modelClass, response.data ) );
	};
}

/**
 * Creates a callback for reading models using the REST API.
 *
 * @param {BuildURLFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {ReadFn} The callback for the repository.
 */
export function restRead< T extends ModelRepositoryParams >(
	buildURL: HasParent< T, never, BuildURLFn >,
	modelClass: ModelConstructor< ModelClass< T > >,
	httpClient: HTTPClient,
	transformer: ModelTransformer< ModelClass< T > >,
): ReadFn< T > {
	return async ( id ) => {
		const response = await httpClient.get( buildURL( id ) );
		return Promise.resolve( transformer.toModel( modelClass, response.data ) );
	};
}

/**
 * Creates a callback for reading child models using the REST API.
 *
 * @param {BuildURLWithParentFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {ReadChildFn} The callback for the repository.
 */
export function restReadChild< T extends ModelRepositoryParams >(
	buildURL: HasParent< T, BuildURLWithParentFn< T >, never >,
	modelClass: ModelConstructor< ModelClass< T > >,
	httpClient: HTTPClient,
	transformer: ModelTransformer< ModelClass< T > >,
): ReadChildFn< T > {
	return async ( parent, id ) => {
		const response = await httpClient.get( buildURL( parent, id ) );
		return Promise.resolve( transformer.toModel( modelClass, response.data ) );
	};
}

/**
 * Creates a callback for updating models using the REST API.
 *
 * @param {BuildURLFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {UpdateFn} The callback for the repository.
 */
export function restUpdate< T extends ModelRepositoryParams >(
	buildURL: HasParent< T, never, BuildURLFn >,
	modelClass: ModelConstructor< ModelClass< T > >,
	httpClient: HTTPClient,
	transformer: ModelTransformer< ModelClass< T > >,
): UpdateFn< T > {
	return async ( id, params ) => {
		const response = await httpClient.patch(
			buildURL( id ),
			transformer.fromModel( params as any ),
		);

		return Promise.resolve( transformer.toModel( modelClass, response.data ) );
	};
}

/**
 * Creates a callback for updating child models using the REST API.
 *
 * @param {BuildURLWithParentFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {UpdateChildFn} The callback for the repository.
 */
export function restUpdateChild< T extends ModelRepositoryParams >(
	buildURL: HasParent< T, BuildURLWithParentFn< T >, never >,
	modelClass: ModelConstructor< ModelClass< T > >,
	httpClient: HTTPClient,
	transformer: ModelTransformer< ModelClass< T > >,
): UpdateChildFn< T > {
	return async ( parent, id, params ) => {
		const response = await httpClient.patch(
			buildURL( parent, id ),
			transformer.fromModel( params as any ),
		);

		return Promise.resolve( transformer.toModel( modelClass, response.data ) );
	};
}

/**
 * Creates a callback for deleting models using the REST API.
 *
 * @param {BuildURLFn} buildURL A callback to build the URL for the request.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @return {DeleteFn} The callback for the repository.
 */
export function restDelete< T extends ModelRepositoryParams >(
	buildURL: HasParent< T, never, BuildURLFn >,
	httpClient: HTTPClient,
): DeleteFn {
	return ( id ) => {
		return httpClient.delete( buildURL( id ) ).then( () => true );
	};
}

/**
 * Creates a callback for deleting child models using the REST API.
 *
 * @param {BuildURLWithParentFn} buildURL A callback to build the URL for the request.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @return {DeleteChildFn} The callback for the repository.
 */
export function restDeleteChild< T extends ModelRepositoryParams >(
	buildURL: HasParent< T, BuildURLWithParentFn< T >, never >,
	httpClient: HTTPClient,
): DeleteChildFn< T > {
	return ( parent, id ) => {
		return httpClient.delete( buildURL( parent, id ) ).then( () => true );
	};
}

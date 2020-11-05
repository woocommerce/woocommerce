import { Model } from '../models/model';

/**
 * A map for converting between API/Model keys.
 *
 * @typedef KeyChangeMap
 * @alias Object.<string,string>
 */
type KeyChangeMap< F, T > = {
	[ key in keyof F ]?: keyof T;
}

/**
 * A map for transformations between API/Model data.
 *
 * @typedef TransformationMap
 * @alias Object.<string,Function>
 */
type TransformationMap< T > = {
	[ key in keyof T ]?: ( val: any ) => any;
}

export namespace ModelTransformer {
	/**
	 * Given an object, this function will transform it using the described key changes and transformations.
	 *
	 * @param {*} data
	 * @param {KeyChangeMap} keyChanges A map of keys to change into another key.
	 * @param {TransformationMap} transformations A map of transformations to perform on values keyed by the key.
	 * @return {*} The transformed data.
	 * @private
	 */
	function transform< F, T >(
		data: F,
		keyChanges?: KeyChangeMap< F, T >,
		transformations?: TransformationMap< F >,
	): any {
		const transformed: any = {};

		for ( const inputKey in data ) {
			let key: string = inputKey;
			if ( keyChanges && keyChanges.hasOwnProperty( inputKey ) ) {
				key = keyChanges[ inputKey ] as string;
			}

			let value = data[ inputKey ];
			if ( transformations && transformations.hasOwnProperty( inputKey ) ) {
				value = ( transformations[ inputKey ] as ( val: any ) => any )( value );
			}

			transformed[ key ] = value;
		}

		return transformed;
	}

	/**
	 * Given a model this will transform it into a standard object.
	 *
	 * @param {F} data The model data to transform.
	 * @param {KeyChangeMap} keyChanges A map of keys to change into another key.
	 * @param {TransformationMap} transformations A map of transformations to perform on values keyed by the key.
	 * @return {*} The transformed data.
	 * @template F
	 */
	export function fromModel< F extends Partial< Model > >(
		data: F,
		keyChanges?: KeyChangeMap< F, any >,
		transformations?: TransformationMap< F >,
	): any {
		return transform( data, keyChanges, transformations );
	}

	/**
	 * Given an object this will transform it into a model object.
	 *
	 * @param {Function} modelClass The model class we want to instantiate.
	 * @param {*} data The raw data to transform.
	 * @param {KeyChangeMap} keyChanges A map of keys to change into another key.
	 * @param {TransformationMap} transformations A map of transformations to perform on values keyed by the key.
	 * @return {T} The created model.
	 * @template T
	 */
	export function toModel< T extends Model, F >(
		modelClass: new ( properties: Partial< T > ) => T,
		data: F,
		keyChanges?: KeyChangeMap< F, T >,
		transformations?: TransformationMap< F >,
	): T {
		return new modelClass( transform( data, keyChanges, transformations ) );
	}
}

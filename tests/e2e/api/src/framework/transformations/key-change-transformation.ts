import { ModelTransformation, TransformationOrder } from '../model-transformer';
import { Model } from '../../models';

/**
 * @typedef KeyChanges
 * @alias Object.<string,string>
 */
type KeyChanges< T extends Model > = { readonly [ key in keyof Partial< T > ]: string };

/**
 * A model transformation that can be used to change property keys between two formats.
 * This transformation has a very high priority so that it will be executed after all
 * other transformations to prevent the changed key from causing problems.
 */
export class KeyChangeTransformation< T extends Model > implements ModelTransformation {
	/**
	 * Ensure that this transformation always happens at the very end since it changes the keys
	 * in the transformed object.
	 */
	public readonly fromModelOrder = TransformationOrder.VeryLast + 1;

	/**
	 * The key change transformations that this object should perform.
	 * This is structured with the model's property key as the key
	 * of the object and the raw property key as the value.
	 *
	 * @type {KeyChanges}
	 * @private
	 */
	private readonly changes: KeyChanges< T >;

	/**
	 * Creates a new transformation.
	 *
	 * @param {KeyChanges} changes The changes we want the transformation to make.
	 */
	public constructor( changes: KeyChanges< T > ) {
		this.changes = changes;
	}

	/**
	 * Performs a transformation from model properties to raw properties.
	 *
	 * @param {*} properties The properties to transform.
	 * @return {*} The transformed properties.
	 */
	public fromModel( properties: any ): any {
		for ( const key in this.changes ) {
			const value = this.changes[ key ];

			if ( ! properties.hasOwnProperty( key ) ) {
				continue;
			}

			properties[ value ] = properties[ key ];
			delete properties[ key ];
		}

		return properties;
	}

	/**
	 * Performs a transformation from raw properties to model properties.
	 *
	 * @param {*} properties The properties to transform.
	 * @return {*} The transformed properties.
	 */
	public toModel( properties: any ): any {
		for ( const key in this.changes ) {
			const value = this.changes[ key ];

			if ( ! properties.hasOwnProperty( value ) ) {
				continue;
			}

			properties[ key ] = properties[ value ];
			delete properties[ value ];
		}

		return properties;
	}
}

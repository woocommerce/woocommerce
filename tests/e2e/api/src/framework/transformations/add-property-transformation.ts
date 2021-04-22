import { ModelTransformation, TransformationOrder } from '../model-transformer';

/**
 * @typedef AdditionalProperties
 * @alias Object.<string,string>
 */
type AdditionalProperties = { [ key: string ]: any };

/**
 * A model transformation that adds a property with
 * a default value if it is not already set.
 */
export class AddPropertyTransformation implements ModelTransformation {
	public readonly fromModelOrder = TransformationOrder.Normal;

	/**
	 *The additional properties to add when executing toModel.
	 *
	 * @type {AdditionalProperties}
	 * @private
	 */
	private readonly toProperties: AdditionalProperties;

	/**
	 * The additional properties to add when executing fromModel.
	 *
	 * @type {AdditionalProperties}
	 * @private
	 */
	private readonly fromProperties: AdditionalProperties;

	/**
	 * Creates a new transformation.
	 *
	 * @param {AdditionalProperties} toProperties The properties to add when executing toModel.
	 * @param {AdditionalProperties} fromProperties The properties to add when executing fromModel.
	 */
	public constructor( toProperties: AdditionalProperties, fromProperties: AdditionalProperties ) {
		this.toProperties = toProperties;
		this.fromProperties = fromProperties;
	}

	/**
	 * Performs a transformation from model properties to raw properties.
	 *
	 * @param {*} properties The properties to transform.
	 * @return {*} The transformed properties.
	 */
	public fromModel( properties: any ): any {
		for ( const key in this.fromProperties ) {
			if ( properties.hasOwnProperty( key ) ) {
				continue;
			}
			properties[ key ] = this.fromProperties[ key ];
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
		for ( const key in this.toProperties ) {
			if ( properties.hasOwnProperty( key ) ) {
				continue;
			}
			properties[ key ] = this.toProperties[ key ];
		}

		return properties;
	}
}

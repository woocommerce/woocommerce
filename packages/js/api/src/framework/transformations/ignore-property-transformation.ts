import { ModelTransformation, TransformationOrder } from '../model-transformer';

export class IgnorePropertyTransformation implements ModelTransformation {
	public readonly fromModelOrder = TransformationOrder.Normal;

	/**
	 * A list of properties that should be removed.
	 *
	 * @type {Array.<string>}
	 * @private
	 */
	private readonly ignoreList: readonly string[];

	/**
	 * Creates a new transformation.
	 *
	 * @param {Array.<string>} ignoreList The properties to ignore.
	 */
	public constructor( ignoreList: string[] ) {
		this.ignoreList = ignoreList;
	}

	/**
	 * Performs a transformation from model properties to raw properties.
	 *
	 * @param {*} properties The properties to transform.
	 * @return {*} The transformed properties.
	 */
	public fromModel( properties: any ): any {
		for ( const key of this.ignoreList ) {
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
		for ( const key of this.ignoreList ) {
			delete properties[ key ];
		}

		return properties;
	}
}

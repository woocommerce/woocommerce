import { ModelTransformation } from '../model-transformer';

/**
 * A callback for transforming model properties.
 *
 * @callback TransformationCallback
 * @param {*} properties The properties to transform.
 * @return {*} The transformed properties.
 */
type TransformationCallback = ( properties: any ) => any;

/**
 * A model transformer for executing arbitrary callbacks on input properties.
 */
export class CustomTransformation implements ModelTransformation {
	public readonly fromModelOrder: number;

	/**
	 * The hook to run for toModel.
	 *
	 * @type {TransformationCallback|null}
	 * @private
	 */
	private readonly toHook: TransformationCallback | null;

	/**
	 * The hook to run for fromModel.
	 *
	 * @type {TransformationCallback|null}
	 * @private
	 */
	private readonly fromHook: TransformationCallback | null;

	/**
	 * Creates a new transformation.
	 *
	 * @param {number} order The order for the transformation.
	 * @param {TransformationCallback|null} toHook The hook to run for toModel.
	 * @param {TransformationCallback|null} fromHook The hook to run for fromModel.
	 */
	public constructor(
		order: number,
		toHook: TransformationCallback | null,
		fromHook: TransformationCallback | null,
	) {
		this.fromModelOrder = order;
		this.toHook = toHook;
		this.fromHook = fromHook;
	}

	/**
	 * Performs a transformation from model properties to raw properties.
	 *
	 * @param {*} properties The properties to transform.
	 * @return {*} The transformed properties.
	 */
	public fromModel( properties: any ): any {
		if ( ! this.fromHook ) {
			return properties;
		}

		return this.fromHook( properties );
	}

	/**
	 * Performs a transformation from raw properties to model properties.
	 *
	 * @param {*} properties The properties to transform.
	 * @return {*} The transformed properties.
	 */
	public toModel( properties: any ): any {
		if ( ! this.toHook ) {
			return properties;
		}

		return this.toHook( properties );
	}
}

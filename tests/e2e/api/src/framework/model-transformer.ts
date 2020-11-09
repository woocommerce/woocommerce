import { Model } from '../models/model';
import { ModelConstructor } from '../models/shared-types';

/**
 * An interface for an object that can perform transformations both to and from a representation
 * and return the input data after performing the desired transformation.
 *
 * @interface ModelTransformation
 */
export interface ModelTransformation {
	/**
	 * The order of execution for the transformer.
	 * - For "fromModel" higher numbers execute later.
	 * - For "toModel" the order is reversed.
	 *
	 * @type {number}
	 */
	readonly fromModelOrder: number;

	/**
	 * Performs a transformation from model properties to raw properties.
	 *
	 * @param {*} properties The properties to transform.
	 * @return {*} The transformed properties.
	 */
	fromModel( properties: any ): any;

	/**
	 * Performs a transformation from raw properties to model properties.
	 *
	 * @param {*} properties The properties to transform.
	 * @return {*} The transformed properties.
	 */
	toModel( properties: any ): any;
}

/**
 * An enum for defining the "toModel" transformation order values.
 */
export enum TransformationOrder {
	First = 0,
	Normal = 500000,
	Last = 1000000,

	/**
	 * A special value reserved for transformations that MUST come after all orders due to
	 * the way that they destroy the property keys or values.
	 */
	VeryLast = 2000000
}

/**
 * A class for transforming models to/from a generic representation.
 */
export class ModelTransformer< T extends Model > {
	/**
	 * An array of transformations to use when converting data to/from models.
	 *
	 * @type {Array.<ModelTransformation>}
	 * @private
	 */
	private transformations: readonly ModelTransformation[];

	/**
	 * Creates a new model transformer instance.
	 *
	 * @param {Array.<ModelTransformation>} transformations The transformations to use.
	 */
	public constructor( transformations: ModelTransformation[] ) {
		// Ensure that the transformations are sorted by priority.
		transformations.sort( ( a, b ) => ( a.fromModelOrder > b.fromModelOrder ) ? 1 : -1 );

		this.transformations = transformations;
	}

	/**
	 * Takes the input model and runs all of the transformations on it before returning the data.
	 *
	 * @param {Partial.<T>} model The model to transform.
	 * @return {*} The transformed data.
	 * @template T
	 */
	public fromModel( model: Partial< T > ): any {
		// Convert the model class to raw properties so that the transformations can be simple.
		const raw = Object.assign( {}, model );

		return this.transformations.reduce(
			( properties: any, transformer: ModelTransformation ) => {
				return transformer.fromModel( properties );
			},
			raw,
		);
	}

	/**
	 * Takes the input data and runs all of the transformations on it before returning the created model.
	 *
	 * @param {Function.<T>} modelClass The model class we're trying to create.
	 * @param {*} data The data we're transforming.
	 * @return {T} The transformed model.
	 * @template T
	 */
	public toModel( modelClass: ModelConstructor< T >, data: any ): T {
		const transformed: any = this.transformations.reduceRight(
			( properties: any, transformer: ModelTransformation ) => {
				return transformer.toModel( properties );
			},
			data,
		);

		return new modelClass( transformed );
	}
}

import { Model } from '../models/model';

/**
 * An interface for an object that can perform transformations both to and from a representation
 * and return the input data after performing the desired transformation.
 *
 * @interface ModelTransformation
 */
export interface ModelTransformation {
	readonly priority: number;
	toModel( properties: any ): any;
	fromModel( properties: any ): any;
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
		transformations.sort( ( a, b ) => ( a.priority > b.priority ) ? 1 : -1 );

		this.transformations = transformations;
	}

	/**
	 * Takes the input data and runs all of the transformations on it before returning the created model.
	 *
	 * @param {Function.<T>} modelClass The model class we're trying to create.
	 * @param {*} data The data we're transforming.
	 * @return {T} The transformed model.
	 * @template T
	 */
	public toModel( modelClass: new( properties: Partial< T > ) => T, data: any ): T {
		const transformed: any = this.transformations.reduce(
			( properties: any, transformer: ModelTransformation ) => {
				return transformer.toModel( properties );
			},
			data,
		);

		return new modelClass( transformed );
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
		const raw = JSON.parse( JSON.stringify( model ) );

		return this.transformations.reduce(
			( properties: any, transformer: ModelTransformation ) => {
				return transformer.fromModel( properties );
			},
			raw,
		);
	}
}

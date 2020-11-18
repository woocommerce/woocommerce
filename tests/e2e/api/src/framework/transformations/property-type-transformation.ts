import { ModelTransformation, TransformationOrder } from '../model-transformer';

/**
 * An enum defining all of the property types that we might want to transform.
 *
 * @enum {number}
 */
export enum PropertyType {
	String,
	Integer,
	Float,
	Boolean,
	Date,
}
type PropertyTypeTypes = null | string | number | boolean | Date;

/**
 * A callback that can be used to transform property types.
 *
 * @callback PropertyTypeCallback
 * @param {*} value The value to transform.
 * @return {*} The transformed value.
 */
type PropertyTypeCallback = ( value: any ) => any;

/**
 * The types for all of a model's properties.
 *
 * @typedef PropertyTypes
 * @alias Object.<string,PropertyType>
 */
type PropertyTypes = { [ key: string ]: PropertyType | PropertyTypeCallback };

/**
 * A model transformer for converting property types between representation formats.
 */
export class PropertyTypeTransformation implements ModelTransformation {
	/**
	 * We want the type transformation to take place after all of the others,
	 * since they may be operating on internal data types.
	 */
	public readonly fromModelOrder = TransformationOrder.VeryLast;

	/**
	 * The property types we will want to transform.
	 *
	 * @type {PropertyTypes}
	 * @private
	 */
	private readonly types: PropertyTypes;

	/**
	 * Creates a new transformation.
	 *
	 * @param {PropertyTypes} types The property types we want to transform.
	 */
	public constructor( types: PropertyTypes ) {
		this.types = types;
	}

	/**
	 * Performs a transformation from model properties to raw properties.
	 *
	 * @param {*} properties The properties to transform.
	 * @return {*} The transformed properties.
	 */
	public fromModel( properties: any ): any {
		for ( const key in this.types ) {
			if ( ! properties.hasOwnProperty( key ) ) {
				continue;
			}
			const value = properties[ key ];

			const type = this.types[ key ];
			if ( type instanceof Function ) {
				properties[ key ] = type( value );
				continue;
			}

			properties[ key ] = this.convertFrom( value, type );
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
		for ( const key in this.types ) {
			if ( ! properties.hasOwnProperty( key ) ) {
				continue;
			}
			const value = properties[ key ];

			const type = this.types[ key ];
			if ( type instanceof Function ) {
				properties[ key ] = type( value );
				continue;
			}

			properties[ key ] = this.convertTo( value, type );
		}

		return properties;
	}

	/**
	 * Converts the given value into the requested type.
	 *
	 * @param {*} value The value to transform.
	 * @param {PropertyType} type The type to transform it into.
	 * @return {*} The converted type.
	 * @private
	 */
	private convertTo( value: any, type: PropertyType ): PropertyTypeTypes | PropertyTypeTypes[] {
		if ( Array.isArray( value ) ) {
			return value.map( ( v: string ) => this.convertTo( v, type ) as PropertyTypeTypes );
		}

		if ( null === value ) {
			return null;
		}

		switch ( type ) {
			case PropertyType.String: return String( value );
			case PropertyType.Integer: return parseInt( value );
			case PropertyType.Float: return parseFloat( value );
			case PropertyType.Boolean: return Boolean( value );
			case PropertyType.Date:
				return new Date( value );
		}
	}

	/**
	 * Converts the given type into a string.
	 *
	 * @param {*} value The value to transform.
	 * @param {PropertyType} type The type to transform it into.
	 * @return {*} The converted type.
	 * @private
	 */
	private convertFrom( value: PropertyTypeTypes | PropertyTypeTypes[], type: PropertyType ): any {
		if ( Array.isArray( value ) ) {
			return value.map( ( v ) => this.convertFrom( v, type ) );
		}

		if ( null === value ) {
			return null;
		}

		switch ( type ) {
			case PropertyType.String:
			case PropertyType.Integer:
			case PropertyType.Float:
			case PropertyType.Boolean:
				return String( value );

			case PropertyType.Date: {
				return ( value as Date ).toISOString();
			}
		}
	}
}

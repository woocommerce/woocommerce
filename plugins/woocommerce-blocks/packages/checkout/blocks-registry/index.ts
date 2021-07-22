/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';
import { registerBlockComponent } from '@woocommerce/blocks-registry';
import { registerExperimentalBlockType } from '@woocommerce/block-settings';
import type { LazyExoticComponent } from 'react';
import { isObject } from '@woocommerce/types';

/**
 * List of block areas where blocks can be registered for use. Keyed by area name.
 */
export type RegisteredBlocks = {
	fields: Array< string >;
	totals: Array< string >;
	contactInformation: Array< string >;
	shippingAddress: Array< string >;
	billingAddress: Array< string >;
	shippingMethods: Array< string >;
	paymentMethods: Array< string >;
};

let registeredBlocks: RegisteredBlocks = {
	fields: [],
	totals: [],
	contactInformation: [ 'core/paragraph' ],
	shippingAddress: [ 'core/paragraph' ],
	billingAddress: [ 'core/paragraph' ],
	shippingMethods: [ 'core/paragraph' ],
	paymentMethods: [ 'core/paragraph' ],
};

/**
 * Asserts that an option is of the given type. Otherwise, throws an error.
 *
 * @throws Will throw an error if the type of the option doesn't match the expected type.
 */
const assertType = (
	optionName: string,
	option: unknown,
	expectedType: unknown
): void => {
	const actualType = typeof option;
	if ( actualType !== expectedType ) {
		throw new Error(
			`Incorrect value for the ${ optionName } argument when registering a checkout block. It was a ${ actualType }, but must be a ${ expectedType }.`
		);
	}
};

/**
 * Validation to ensure an area exists.
 */
const assertValidArea = ( area: string ): void => {
	if ( ! registeredBlocks.hasOwnProperty( area ) ) {
		throw new Error(
			`Incorrect value for the "area" argument. It was a ${ area }, but must be one of ${ Object.keys(
				registeredBlocks
			).join( ', ' ) }.`
		);
	}
};

/**
 * Validate the block name.
 *
 * @throws Will throw an error if the blockname is invalid.
 */
const assertBlockName = ( blockName: string ): void => {
	assertType( 'blockName', blockName, 'string' );

	if ( ! blockName ) {
		throw new Error(
			`Value for the blockName argument must not be empty.`
		);
	}
};

/**
 * Asserts that an option is of the given type. Otherwise, throws an error.
 *
 * @throws Will throw an error if the type of the option doesn't match the expected type.
 */
const assertOption = (
	options: Record< string, unknown >,
	optionName: string,
	expectedType: 'array' | 'object' | 'string' | 'boolean' | 'number'
): void => {
	const actualType = typeof options[ optionName ];

	if ( expectedType === 'array' ) {
		if ( ! Array.isArray( options[ optionName ] ) ) {
			throw new Error(
				`Incorrect value for the ${ optionName } argument when registering a checkout block component. It was a ${ actualType }, but must be an array.`
			);
		}
	} else if ( actualType !== expectedType ) {
		throw new Error(
			`Incorrect value for the ${ optionName } argument when registering a checkout block component. It was a ${ actualType }, but must be a ${ expectedType }.`
		);
	}
};

/**
 * Asserts that an option is a valid react element or lazy callback. Otherwise, throws an error.
 *
 * @throws Will throw an error if the type of the option doesn't match the expected type.
 */
const assertBlockComponent = (
	options: Record< string, unknown >,
	optionName: string
) => {
	const optionValue = options[ optionName ];

	if ( optionValue ) {
		if ( typeof optionValue === 'function' ) {
			return;
		}
		if (
			isObject( optionValue ) &&
			optionValue.$$typeof &&
			optionValue.$$typeof === Symbol.for( 'react.lazy' )
		) {
			return;
		}
	}
	throw new Error(
		`Incorrect value for the ${ optionName } argument when registering a block component. Component must be a valid React Element or Lazy callback.`
	);
};

/**
 * Adds a block (block name) to an area, if the area exists. If the area does not exist, an error is thrown.
 */
const registerBlockForArea = (
	area: keyof RegisteredBlocks,
	blockName: string
): void | Error => {
	assertValidArea( area );
	registeredBlocks = {
		...registeredBlocks,
		[ area ]: [ ...registeredBlocks[ area ], blockName ],
	};
};

/**
 * Get a list of blocks available within a specific area.
 */
export const getRegisteredBlocks = (
	area: keyof RegisteredBlocks
): Array< string > => {
	assertValidArea( area );
	return [ ...registeredBlocks[ area ] ];
};

export type CheckoutBlockOptions = {
	// This is a component to render on the frontend in place of this block, when used.
	component:
		| LazyExoticComponent< React.ComponentType< unknown > >
		| JSX.Element;
	// Area(s) to add the block to. This can be a single area (string) or an array of areas.
	areas: Array< keyof RegisteredBlocks >;
	// Standard block configuration object. If not passed, the block will not be registered with WordPress and must be done manually.
	configuration?: BlockConfiguration;
};

/**
 * Main API for registering a new checkout block within areas.
 */
export const registerCheckoutBlock = (
	blockName: string,
	options: CheckoutBlockOptions
): void => {
	assertBlockName( blockName );
	assertOption( options, 'areas', 'array' );
	assertBlockComponent( options, 'component' );

	if ( options?.configuration ) {
		assertOption( options, 'configuration', 'object' );
		registerExperimentalBlockType( blockName, {
			...options.configuration,
			category: 'woocommerce',
		} );
	}

	options.areas.forEach( ( area ) =>
		registerBlockForArea( area, blockName )
	);

	registerBlockComponent( {
		blockName,
		component: options.component,
	} );
};

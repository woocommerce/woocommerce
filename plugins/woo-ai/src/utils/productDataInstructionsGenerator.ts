/**
 * Internal dependencies
 */
import { Attribute } from './types';
import {
	getAttributes,
	getCategories,
	getDescription,
	getProductName,
	getTags,
	ProductProps,
} from '.';
import { DESCRIPTION_MAX_LENGTH, MAX_TITLE_LENGTH } from '../constants';

type PropsFilter = {
	excludeProps?: ProductProps[];
	allowedProps?: ProductProps[];
};

type InstructionSet = {
	includedProps: string[];
	instructions: string[];
};

/**
 * Function to generate prompt instructions for product data.
 *
 * @param {PropsFilter}    propsFilter              Object containing the props to be included or excluded from the instructions.
 * @param {ProductProps[]} propsFilter.excludeProps Array of props to be excluded from the instructions.
 * @param {ProductProps[]} propsFilter.allowedProps Array of props to be included in the instructions.
 *
 * @return {string[]} Array of prompt instructions.
 */
export const generateProductDataInstructions = ( {
	excludeProps,
	allowedProps,
}: PropsFilter = {} ): InstructionSet => {
	const isPropertyAllowed = ( prop: ProductProps ): boolean => {
		if ( allowedProps ) {
			return allowedProps.includes( prop );
		}

		if ( excludeProps ) {
			return ! excludeProps.includes( prop );
		}

		return true;
	};

	const productName: string = isPropertyAllowed( ProductProps.Name )
		? getProductName()
		: '';
	const productDescription: string = isPropertyAllowed(
		ProductProps.Description
	)
		? getDescription()
		: '';
	const productCategories: string[] = isPropertyAllowed(
		ProductProps.Categories
	)
		? getCategories()
		: [];
	const productTags: string[] = isPropertyAllowed( ProductProps.Tags )
		? getTags()
		: [];
	const productAttributes: Attribute[] = isPropertyAllowed(
		ProductProps.Attributes
	)
		? getAttributes()
		: [];

	const includedProps: string[] = [];
	const productPropsInstructions: string[] = [];
	if ( productName ) {
		productPropsInstructions.push(
			`Name: ${ productName.slice( 0, MAX_TITLE_LENGTH ) }.`
		);
		includedProps.push( 'name' );
	}
	if ( productDescription ) {
		productPropsInstructions.push(
			`Description: ${ productDescription.slice(
				0,
				DESCRIPTION_MAX_LENGTH
			) }.`
		);
		includedProps.push( ProductProps.Description );
	}
	if ( productCategories.length ) {
		productPropsInstructions.push(
			`Product categories: ${ productCategories.join( ', ' ) }.`
		);
		includedProps.push( ProductProps.Categories );
	}
	if ( productTags.length ) {
		productPropsInstructions.push(
			`Tagged with: ${ productTags.join( ', ' ) }.`
		);
		includedProps.push( ProductProps.Tags );
	}
	if ( productAttributes.length ) {
		productAttributes.forEach( ( { name, values } ) => {
			productPropsInstructions.push(
				`${ name }: ${ values.join( ', ' ) }.`
			);
			includedProps.push( name );
		} );
	}

	return {
		includedProps,
		instructions: productPropsInstructions,
	};
};

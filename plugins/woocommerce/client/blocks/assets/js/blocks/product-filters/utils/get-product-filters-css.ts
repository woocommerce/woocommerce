/**
 * Internal dependencies
 */
import { getColorsFromBlockSupports } from './get-colors-from-block-supports';
import type { BlockAttributes } from '../types';

const isNull = < T >( term: T | null ): term is null => {
	return term === null;
};

const isObject = < T extends Record< string, unknown >, U >(
	term: T | U
): term is NonNullable< T > => {
	return (
		! isNull( term ) &&
		term instanceof Object &&
		term.constructor === Object
	);
};

const isString = < U >( term: string | U ): term is string => {
	return typeof term === 'string';
};

function objectHasProp< P extends PropertyKey >(
	target: unknown,
	property: P
): target is { [ K in P ]: unknown } {
	// The `in` operator throws a `TypeError` for non-object values.
	return isObject( target ) && property in target;
}

function presetToCssVariable( preset: string ) {
	if ( ! preset.includes( ':' ) || ! preset.includes( '|' ) ) {
		return preset;
	}

	return `var(--wp--${ preset
		.replace( 'var:', '' )
		.replaceAll( '|', '--' ) })`;
}

export function getProductFiltersCss( attributes: BlockAttributes ) {
	const colors = getColorsFromBlockSupports( attributes );
	const styles: Record< string, string | undefined > = {
		'--wc-product-filters-text-color': colors.textColor || '#111',
		'--wc-product-filters-background-color':
			colors.backgroundColor || '#fff',
	};
	if (
		objectHasProp( attributes, 'style' ) &&
		objectHasProp( attributes.style, 'spacing' ) &&
		objectHasProp( attributes.style.spacing, 'blockGap' ) &&
		isString( attributes.style.spacing.blockGap )
	) {
		styles[ '--wc-product-filter-block-spacing' ] = presetToCssVariable(
			attributes.style.spacing.blockGap
		);
	}
	return styles;
}

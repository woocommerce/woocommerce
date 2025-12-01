/**
 * Internal dependencies
 */
import { getColorCSSVar } from '../../utils/colors';
import { BlockAttributes } from './types';

export function getColorVars( attributes: BlockAttributes ) {
	const {
		chipText,
		chipBackground,
		chipBorder,
		customChipText,
		customChipBackground,
		customChipBorder,
	} = attributes;

	const vars: Record< string, string > = {
		'--wc-product-filter-removable-chips-text': getColorCSSVar(
			chipText,
			customChipText
		),
		'--wc-product-filter-removable-chips-background': getColorCSSVar(
			chipBackground,
			customChipBackground
		),
		'--wc-product-filter-removable-chips-border': getColorCSSVar(
			chipBorder,
			customChipBorder
		),
	};

	return Object.keys( vars ).reduce(
		( acc: Record< string, string >, key ) => {
			if ( vars[ key ] ) {
				acc[ key ] = vars[ key ];
			}
			return acc;
		},
		{}
	);
}

export function getColorClasses( attributes: BlockAttributes ) {
	const {
		chipText,
		chipBackground,
		chipBorder,
		customChipText,
		customChipBackground,
		customChipBorder,
	} = attributes;

	return {
		'has-chip-text-color': chipText || customChipText,
		'has-chip-background-color': chipBackground || customChipBackground,
		'has-chip-border-color': chipBorder || customChipBorder,
	};
}

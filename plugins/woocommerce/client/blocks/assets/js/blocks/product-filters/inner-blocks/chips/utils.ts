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
		selectedChipText,
		selectedChipBackground,
		selectedChipBorder,
		customChipText,
		customChipBackground,
		customChipBorder,
		customSelectedChipText,
		customSelectedChipBackground,
		customSelectedChipBorder,
	} = attributes;

	const vars: Record< string, string > = {
		'--wc-product-filter-chips-text': getColorCSSVar(
			chipText,
			customChipText
		),
		'--wc-product-filter-chips-background': getColorCSSVar(
			chipBackground,
			customChipBackground
		),
		'--wc-product-filter-chips-border': getColorCSSVar(
			chipBorder,
			customChipBorder
		),
		'--wc-product-filter-chips-selected-text': getColorCSSVar(
			selectedChipText,
			customSelectedChipText
		),
		'--wc-product-filter-chips-selected-background': getColorCSSVar(
			selectedChipBackground,
			customSelectedChipBackground
		),
		'--wc-product-filter-chips-selected-border': getColorCSSVar(
			selectedChipBorder,
			customSelectedChipBorder
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
		selectedChipText,
		selectedChipBackground,
		selectedChipBorder,
		customChipText,
		customChipBackground,
		customChipBorder,
		customSelectedChipText,
		customSelectedChipBackground,
		customSelectedChipBorder,
	} = attributes;

	return {
		'has-chip-text-color': chipText || customChipText,
		'has-chip-background-color': chipBackground || customChipBackground,
		'has-chip-border-color': chipBorder || customChipBorder,
		'has-selected-chip-text-color':
			selectedChipText || customSelectedChipText,
		'has-selected-chip-background-color':
			selectedChipBackground || customSelectedChipBackground,
		'has-selected-chip-border-color':
			selectedChipBorder || customSelectedChipBorder,
	};
}

/**
 * Internal dependencies
 */
import { BlockAttributes } from './types';

function getCSSVar( slug: string | undefined, value: string | undefined ) {
	if ( slug ) {
		return `var(--wp--preset--color--${ slug })`;
	}
	return value || '';
}

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
		'--wc-product-filter-chips-text': getCSSVar( chipText, customChipText ),
		'--wc-product-filter-chips-background': getCSSVar(
			chipBackground,
			customChipBackground
		),
		'--wc-product-filter-chips-border': getCSSVar(
			chipBorder,
			customChipBorder
		),
		'--wc-product-filter-chips-selected-text': getCSSVar(
			selectedChipText,
			customSelectedChipText
		),
		'--wc-product-filter-chips-selected-background': getCSSVar(
			selectedChipBackground,
			customSelectedChipBackground
		),
		'--wc-product-filter-chips-selected-border': getCSSVar(
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

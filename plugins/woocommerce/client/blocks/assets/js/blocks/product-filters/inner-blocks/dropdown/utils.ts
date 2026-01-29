/**
 * Internal dependencies
 */
import { getColorCSSVar } from '../../utils/colors';
import { BlockAttributes } from './types';

export function getColorVars( attributes: BlockAttributes ) {
	const {
		containerBackground,
		containerBorder,
		badgeBackground,
		badgeText,
		placeholderText,
		customContainerBackground,
		customContainerBorder,
		customBadgeBackground,
		customBadgeText,
		customPlaceholderText,
	} = attributes;

	const vars: Record< string, string > = {
		'--wc-product-filter-dropdown-container-background': getColorCSSVar(
			containerBackground,
			customContainerBackground
		),
		'--wc-product-filter-dropdown-container-border': getColorCSSVar(
			containerBorder,
			customContainerBorder
		),
		'--wc-product-filter-dropdown-badge-background': getColorCSSVar(
			badgeBackground,
			customBadgeBackground
		),
		'--wc-product-filter-dropdown-badge-text': getColorCSSVar(
			badgeText,
			customBadgeText
		),
		'--wc-product-filter-dropdown-placeholder-text': getColorCSSVar(
			placeholderText,
			customPlaceholderText
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
		containerBackground,
		containerBorder,
		badgeBackground,
		badgeText,
		placeholderText,
		customContainerBackground,
		customContainerBorder,
		customBadgeBackground,
		customBadgeText,
		customPlaceholderText,
	} = attributes;

	return {
		'has-container-background-color':
			containerBackground || customContainerBackground,
		'has-container-border-color': containerBorder || customContainerBorder,
		'has-badge-background-color': badgeBackground || customBadgeBackground,
		'has-badge-text-color': badgeText || customBadgeText,
		'has-placeholder-text-color': placeholderText || customPlaceholderText,
	};
}

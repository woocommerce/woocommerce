/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type { Color, FilterBlockContext } from '../../types';

export type BlockAttributes = {
	className: string;
	containerBackground: string;
	customContainerBackground: string;
	containerBorder: string;
	customContainerBorder: string;
	badgeBackground: string;
	customBadgeBackground: string;
	badgeText: string;
	customBadgeText: string;
	placeholderText: string;
	customPlaceholderText: string;
};

export type EditProps = BlockEditProps< BlockAttributes > & {
	context: FilterBlockContext;
	containerBackground: Color;
	setContainerBackground: ( value: string ) => void;
	containerBorder: Color;
	setContainerBorder: ( value: string ) => void;
	badgeBackground: Color;
	setBadgeBackground: ( value: string ) => void;
	badgeText: Color;
	setBadgeText: ( value: string ) => void;
	placeholderText: Color;
	setPlaceholderText: ( value: string ) => void;
};

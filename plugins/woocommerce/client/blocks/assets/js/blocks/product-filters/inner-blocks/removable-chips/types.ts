/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type { ActiveFiltersBlockContext } from '../../../../types/type-defs/active-filters';

export type Color = {
	slug?: string;
	name?: string;
	class?: string;
	color: string;
};

export type BlockAttributes = {
	className: string;
	chipText?: string;
	customChipText?: string;
	chipBackground?: string;
	customChipBackground?: string;
	chipBorder?: string;
	customChipBorder?: string;
	layout: {
		orientation: string;
	};
};

export type EditProps = BlockEditProps< BlockAttributes > & {
	style: Record< string, string >;
	context: ActiveFiltersBlockContext;
	chipText: Color;
	setChipText: ( value: string ) => void;
	chipBackground: Color;
	setChipBackground: ( value: string ) => void;
	chipBorder: Color;
	setChipBorder: ( value: string ) => void;
	name: string;
};

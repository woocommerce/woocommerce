/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';

export interface BlockAttributes {
	className: string;
	attributeId: number;
	showCounts: boolean;
	queryType: string;
	heading: string;
	headingLevel: number;
	displayStyle: string;
	showFilterButton: boolean;
	isPreview: boolean;
}

export interface EditProps extends BlockEditProps< BlockAttributes > {
	debouncedSpeak: ( label: string ) => void;
}

export interface DisplayOption {
	value: string;
	name: string;
	label: JSX.Element;
}

/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

export type BlockAttributes = {
	attributeId: number;
	showCounts: boolean;
	queryType: 'or' | 'and';
	displayStyle: string;
	selectType: string;
	isPreview: boolean;
	hideEmpty: boolean;
	clearButton: boolean;
};

export interface DisplayOption {
	value: string;
	name: string;
	label: JSX.Element;
	textLabel: string;
}

export type Current = {
	slug: string;
	name: string;
};

export type EditProps = BlockEditProps< BlockAttributes >;

/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

export type BlockAttributes = {
	classname?: string;
	showCounts: boolean;
	displayStyle: string;
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

export type CollectionData = {
	stock_status_counts: StatusCount[];
};

export type StatusCount = {
	status: string;
	count: number;
};

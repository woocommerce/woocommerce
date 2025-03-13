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

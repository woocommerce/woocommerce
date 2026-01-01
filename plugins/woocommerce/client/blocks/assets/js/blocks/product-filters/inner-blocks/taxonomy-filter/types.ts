/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';

export type BlockAttributes = {
	taxonomy: string;
	showCounts: boolean;
	displayStyle: string;
	isPreview: boolean;
	sortOrder: string;
	hideEmpty: boolean;
};

export type EditProps = BlockEditProps< BlockAttributes >;

export type TaxonomyItem = {
	name: string;
	label: string;
};

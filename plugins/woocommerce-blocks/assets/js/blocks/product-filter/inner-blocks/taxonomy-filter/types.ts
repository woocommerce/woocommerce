/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

export type BlockAttributes = {
	taxonomyName: string;
	showCounts: boolean;
	queryType: string;
	displayStyle: string;
	selectType: string;
	isPreview: boolean;
};

export interface EditProps extends BlockEditProps< BlockAttributes > {
	debouncedSpeak: ( label: string ) => void;
}

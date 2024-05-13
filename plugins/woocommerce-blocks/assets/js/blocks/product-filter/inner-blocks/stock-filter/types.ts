/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

export interface BlockProps {
	className?: string;
	showCounts: boolean;
	isPreview?: boolean;
	displayStyle: string;
	selectType: string;
	isEditor: boolean;
}

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

export type EditProps = BlockEditProps< BlockProps >;

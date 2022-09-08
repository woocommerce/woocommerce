/**
 * External dependencies
 */
import { Value } from '@wordpress/rich-text';

export type FormatAtts = {
	url?: string;
	type?: string;
	id?: string;
};

export type EditProps = {
	isActive: boolean;
	value: Value;
	activeAttributes: FormatAtts;
	onChange( value: Value ): void;
	onFocus: () => void;
	contentRef?: React.Ref< HTMLElement >;
};

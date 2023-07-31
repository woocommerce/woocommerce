/**
 * External dependencies
 */
import { TemplateArray } from '@wordpress/blocks';

export type Template = {
	id: string;
	title: {
		raw: string;
		rendered: string;
	};
	content: {
		raw: TemplateArray;
	};
};

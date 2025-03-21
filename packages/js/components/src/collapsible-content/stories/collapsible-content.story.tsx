/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CollapsibleContent } from '../';

export const Basic = () => {
	return (
		<CollapsibleContent toggleText="Advanced">
			All this business in here is collapsed.
		</CollapsibleContent>
	);
};

export const Expanded = () => {
	return (
		<CollapsibleContent toggleText="Advanced" initialCollapsed={ false }>
			All this business in here is initially expanded.
		</CollapsibleContent>
	);
};

export default {
	title: 'Components/CollapsibleContent',
	component: Basic,
};

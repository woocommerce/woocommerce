/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CollapsibleContent } from '../';

export const Basic: React.FC = () => {
	return (
		<CollapsibleContent toggleText="Advanced">
			All this business in here is collapsed.
		</CollapsibleContent>
	);
};

export const Expanded: React.FC = () => {
	return (
		<CollapsibleContent toggleText="Advanced" initialCollapsed={ false }>
			All this business in here is initially expanded.
		</CollapsibleContent>
	);
};

export default {
	title: 'WooCommerce Admin/components/CollapsibleContent',
	component: Basic,
};

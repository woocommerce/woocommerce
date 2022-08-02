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

export default {
	title: 'WooCommerce Admin/components/CollapsibleContent',
	component: Basic,
};

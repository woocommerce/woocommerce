/**
 * External dependencies
 */
import { Card } from '@wordpress/components';
import { TablePlaceholder } from '@woocommerce/components';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { headers } from './index';

export const Basic = () => {
	return (
		<Card size="none">
			<TablePlaceholder caption="Revenue last week" headers={ headers } />
		</Card>
	);
};

export default {
	title: 'Components/TablePlaceholder',
	component: TablePlaceholder,
};

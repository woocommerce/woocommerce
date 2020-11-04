/**
 * External dependencies
 */
import { Badge } from '@woocommerce/components';
import { Card, CardBody } from '@wordpress/components';

export const Basic = () => (
	<Card>
		<CardBody>
			<Badge count={ 15 } />
		</CardBody>
	</Card>
);

export default {
	title: 'WooCommerce Admin/components/Badge',
	component: Badge,
};

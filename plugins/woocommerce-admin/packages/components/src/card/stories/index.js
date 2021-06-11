/**
 * External dependencies
 */
import { Card } from '@woocommerce/components';

export const Basic = () => (
	<>
		<Card title="Store Performance" description="Key performance metrics">
			<p>Your stuff in a Card.</p>
		</Card>
	</>
);

export const Inactive = () => (
	<>
		<Card title="Inactive Card" isInactive>
			<p>This Card is grayed out and has no box-shadow.</p>
		</Card>
	</>
);

export default {
	title: 'WooCommerce Admin/components/Card',
	component: Card,
};

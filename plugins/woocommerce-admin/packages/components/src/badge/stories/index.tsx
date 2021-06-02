/**
 * External dependencies
 */
import { Card, CardBody } from '@wordpress/components';
import { Story } from '@storybook/react';

/**
 * Internal dependencies
 */
import { Badge, BadgeProps } from '../';

const Template: Story< BadgeProps > = ( args ) => (
	<Card>
		<CardBody>
			<Badge { ...args } />
		</CardBody>
	</Card>
);

export const Primary = Template.bind( {} );

Primary.args = {
	count: 15,
};

export default {
	title: 'WooCommerce Admin/components/Badge',
	component: Badge,
};

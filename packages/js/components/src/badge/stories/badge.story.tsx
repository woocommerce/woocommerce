/**
 * External dependencies
 */
import { Card, CardBody } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { StoryFn } from '@storybook/react-webpack5';

/**
 * Internal dependencies
 */
import { Badge, BadgeProps } from '../';

const Template: StoryFn< BadgeProps > = ( args ) => (
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
	title: 'Components/Badge',
	component: Badge,
};

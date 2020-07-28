/* eslint-disable no-alert */
/**
 * External dependencies
 */
import { List } from '@woocommerce/components';

/**
 * External dependencies
 */
import Gridicon from 'gridicons';

const listItems = [
	{
		title: 'List item title',
		content: 'List item description text',
	},
	{
		before: <Gridicon icon="star" />,
		title: 'List item with before icon',
		content: 'List item description text',
	},
	{
		before: <Gridicon icon="star" />,
		after: <Gridicon icon="chevron-right" />,
		title: 'List item with before and after icons',
		content: 'List item description text',
	},
	{
		title: 'Clickable list item',
		content: 'List item description text',
		// eslint-disable-next-line no-undef
		onClick: () => alert( 'List item clicked' ),
	},
];

export default () => (
	<div>
		<List items={ listItems } />
	</div>
);

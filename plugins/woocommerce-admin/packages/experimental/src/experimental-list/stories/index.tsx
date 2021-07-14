/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { withConsole } from '@storybook/addon-console';
import { Meta, Story } from '@storybook/react';
/**
 * Internal dependencies
 */
import { List, ListItem, CollapsibleList } from '../..';
import { ListProps } from '../experimental-list';
import './style.scss';

export default {
	title: 'WooCommerce Admin/experimental/List',
	component: List,
	decorators: [ ( storyFn, context ) => withConsole()( storyFn )( context ) ],
} as Meta;

const Template: Story< ListProps > = ( args ) => (
	<List { ...args }>
		<ListItem disableGutters onClick={ () => {} }>
			<div>Without gutters no padding is added to the list item.</div>
		</ListItem>
		<ListItem onClick={ () => {} }>
			<div>Any markup can go here.</div>
		</ListItem>
		<ListItem onClick={ () => {} }>
			<div>Any markup can go here.</div>
		</ListItem>
		<ListItem onClick={ () => {} }>
			<div>Any markup can go here.</div>
		</ListItem>
	</List>
);

export const Primary = Template.bind( { onClick: () => {} } );

Primary.args = {
	listType: 'ul',
	animation: 'slide-right',
};

export const CollapsibleListExample: Story = () => {
	return (
		<CollapsibleList
			collapseLabel="Show less"
			expandLabel="Show more items"
			show={ 2 }
			onCollapse={ () => {
				// eslint-disable-next-line no-console
				console.log( 'collapsed' );
			} }
			onExpand={ () => {
				// eslint-disable-next-line no-console
				console.log( 'expanded' );
			} }
		>
			<ListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ListItem>
			<ListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ListItem>
			<ListItem onClick={ () => {} }>
				<div>
					Any markup can go here.
					<br />
					Bigger task item
					<br />
					Another line
				</div>
			</ListItem>
			<ListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ListItem>
			<ListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ListItem>
		</CollapsibleList>
	);
};

CollapsibleListExample.storyName = 'List with CollapsibleListItem.';

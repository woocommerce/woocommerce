/**
 * External dependencies
 */
import Gridicon from 'gridicons';
import { withConsole } from '@storybook/addon-console';

/**
 * Internal dependencies
 */
import List, {
	ExperimentalList,
	ExperimentalListItem,
	ExperimentalCollapsibleList,
} from '../';
import './style.scss';

function logItemClick( event ) {
	const a = event.currentTarget;
	const itemDescription = a.href
		? `[${ a.textContent }](${ a.href }) ${ a.dataset.linkType }`
		: `[${ a.textContent }]`;
	const itemTag = a.dataset.listItemTag
		? `'${ a.dataset.listItemTag }'`
		: 'not set';
	const logMessage = `[${ itemDescription } item clicked (tag: ${ itemTag })`;

	// eslint-disable-next-line no-console
	console.log( logMessage );

	event.preventDefault();
	return false;
}

export default {
	title: 'WooCommerce Admin/components/List',
	component: List,
	decorators: [ ( storyFn, context ) => withConsole()( storyFn )( context ) ],
};

export const Default = () => {
	const listItems = [
		{
			title: 'WooCommerce.com',
			href: 'https://woocommerce.com',
			onClick: logItemClick,
		},
		{
			title: 'WordPress.org',
			href: 'https://wordpress.org',
			onClick: logItemClick,
		},
		{
			title: 'A list item with no action',
		},
		{
			title: 'Click me!',
			content: 'An alert will be triggered.',
			onClick: ( event ) => {
				// eslint-disable-next-line no-alert
				window.alert( 'List item clicked' );
				return logItemClick( event );
			},
		},
	];

	return <List items={ listItems } />;
};

Default.storyName = 'Default (deprecated)';

export const BeforeAndAfter = () => {
	const listItems = [
		{
			before: <Gridicon icon="cart" />,
			after: <Gridicon icon="chevron-right" />,
			title: 'WooCommerce.com',
			href: 'https://woocommerce.com',
			onClick: logItemClick,
		},
		{
			before: <Gridicon icon="my-sites" />,
			after: <Gridicon icon="chevron-right" />,
			title: 'WordPress.org',
			href: 'https://wordpress.org',
			onClick: logItemClick,
		},
		{
			before: <Gridicon icon="link-break" />,
			title: 'A list item with no action',
			description: 'List item description text',
		},
		{
			before: <Gridicon icon="notice" />,
			title: 'Click me!',
			content: 'An alert will be triggered.',
			onClick: ( event ) => {
				// eslint-disable-next-line no-alert
				window.alert( 'List item clicked' );
				return logItemClick( event );
			},
		},
	];

	return <List items={ listItems } />;
};

BeforeAndAfter.storyName = 'Before and after (deprecated)';

export const CustomStyleAndTags = () => {
	const listItems = [
		{
			before: <Gridicon icon="cart" />,
			after: <Gridicon icon="chevron-right" />,
			title: 'WooCommerce.com',
			href: 'https://woocommerce.com',
			onClick: logItemClick,
			listItemTag: 'woocommerce.com-link',
		},
		{
			before: <Gridicon icon="my-sites" />,
			after: <Gridicon icon="chevron-right" />,
			title: 'WordPress.org',
			href: 'https://wordpress.org',
			onClick: logItemClick,
			listItemTag: 'wordpress.org-link',
		},
		{
			before: <Gridicon icon="link-break" />,
			title: 'A list item with no action',
		},
		{
			before: <Gridicon icon="notice" />,
			title: 'Click me!',
			content: 'An alert will be triggered.',
			onClick: ( event ) => {
				// eslint-disable-next-line no-alert
				window.alert( 'List item clicked' );
				return logItemClick( event );
			},
			listItemTag: 'click-me',
		},
	];

	return <List items={ listItems } className="storybook-custom-list" />;
};

CustomStyleAndTags.storyName = 'Custom style and tags (deprecated)';

export const ExperimentalListExample = () => {
	return (
		<ExperimentalList>
			<ExperimentalListItem disableGutters onClick={ () => {} }>
				<div>Without gutters no padding is added to the list item.</div>
			</ExperimentalListItem>
			<ExperimentalListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ExperimentalListItem>
			<ExperimentalListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ExperimentalListItem>
			<ExperimentalListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ExperimentalListItem>
		</ExperimentalList>
	);
};

ExperimentalListExample.storyName = 'ExperimentalList / ExperimentalListItem.';

export const ExperimentalCollapsibleListExample = () => {
	return (
		<ExperimentalCollapsibleList
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
			<ExperimentalListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ExperimentalListItem>
			<ExperimentalListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ExperimentalListItem>
			<ExperimentalListItem onClick={ () => {} }>
				<div>
					Any markup can go here.
					<br />
					Bigger task item
					<br />
					Another line
				</div>
			</ExperimentalListItem>
			<ExperimentalListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ExperimentalListItem>
			<ExperimentalListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ExperimentalListItem>
		</ExperimentalCollapsibleList>
	);
};

ExperimentalCollapsibleListExample.storyName =
	'ExperimentalList with ExperimentalCollapsibleListItem.';

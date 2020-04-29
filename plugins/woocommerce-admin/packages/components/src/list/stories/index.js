/**
 * External dependencies
 */
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import List from '../';
import './style.scss';

export default {
	title: 'WooCommerce Admin/components/List',
	component: List,
};

export const Default = () => {
	const listItems = [
		{
			title: 'WooCommerce.com',
			href: 'https://woocommerce.com',
		},
		{
			title: 'WordPress.org',
			href: 'https://wordpress.org',
		},
		{
			title: 'A list item with no action',
		},
		{
			title: 'Click me!',
			content: 'An alert will be triggered.',
			onClick: () => {
				// eslint-disable-next-line no-alert
				window.alert( 'List item clicked' );
			},
		},
	];

	return <List items={ listItems } />;
};

export const BeforeAndAfter = () => {
	const listItems = [
		{
			before: <Gridicon icon="cart" />,
			after: <Gridicon icon="chevron-right" />,
			title: 'WooCommerce.com',
			href: 'https://woocommerce.com',
		},
		{
			before: <Gridicon icon="my-sites" />,
			after: <Gridicon icon="chevron-right" />,
			title: 'WordPress.org',
			href: 'https://wordpress.org',
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
			onClick: () => {
				// eslint-disable-next-line no-alert
				window.alert( 'List item clicked' );
			},
		},
	];

	return <List items={ listItems } />;
};

export const CustomStyle = () => {
	const listItems = [
		{
			before: <Gridicon icon="cart" />,
			after: <Gridicon icon="chevron-right" />,
			title: 'WooCommerce.com',
			href: 'https://woocommerce.com',
		},
		{
			before: <Gridicon icon="my-sites" />,
			after: <Gridicon icon="chevron-right" />,
			title: 'WordPress.org',
			href: 'https://wordpress.org',
		},
		{
			before: <Gridicon icon="link-break" />,
			title: 'A list item with no action',
		},
		{
			before: <Gridicon icon="notice" />,
			title: 'Click me!',
			content: 'An alert will be triggered.',
			onClick: () => {
				// eslint-disable-next-line no-alert
				window.alert( 'List item clicked' );
			},
		},
	];

	return <List items={ listItems } className="storybook-custom-list" />;
};

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody, CardHeader } from '@wordpress/components';
import {
	Icon,
	megaphone,
	box,
	brush,
	shipping,
	percent,
	payment,
	pencil,
	lifesaver,
	external,
	chevronRight,
} from '@wordpress/icons';
import { partial } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';
import { H, List } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { recordEvent } from 'lib/tracks';
import './style.scss';

function getItems( props ) {
	return [
		{
			title: __( 'Market my store', 'woocommerce-admin' ),
			type: 'wc-admin',
			path: 'marketing',
			icon: megaphone,
			listItemTag: 'marketing',
		},
		{
			title: __( 'Add products', 'woocommerce-admin' ),
			type: 'wp-admin',
			path: 'post-new.php?post_type=product',
			icon: box,
			listItemTag: 'add-products',
		},
		{
			title: __( 'Personalize my store', 'woocommerce-admin' ),
			type: 'wp-admin',
			path: 'customize.php',
			icon: brush,
			listItemTag: 'personalize-store',
		},
		{
			title: __( 'Shipping settings', 'woocommerce-admin' ),
			type: 'wc-settings',
			tab: 'shipping',
			icon: shipping,
			listItemTag: 'shipping-settings',
		},
		{
			title: __( 'Tax settings', 'woocommerce-admin' ),
			type: 'wc-settings',
			tab: 'tax',
			icon: percent,
			listItemTag: 'tax-settings',
		},
		{
			title: __( 'Payment settings', 'woocommerce-admin' ),
			type: 'wc-settings',
			tab: 'checkout',
			icon: payment,
			listItemTag: 'payment-settings',
		},
		{
			title: __( 'Edit store details', 'woocommerce-admin' ),
			type: 'wc-settings',
			tab: 'general',
			icon: pencil,
			listItemTag: 'edit-store-details',
		},
		{
			title: __( 'Get support', 'woocommerce-admin' ),
			type: 'external',
			href: 'https://woocommerce.com/my-account/create-a-ticket/',
			icon: lifesaver,
			listItemTag: 'support',
		},
		{
			title: __( 'View my store', 'woocommerce-admin' ),
			type: 'external',
			href: props.getSetting( 'siteUrl' ),
			icon: external,
			listItemTag: 'view-store',
		},
	];
}

function handleOnItemClick( props, event ) {
	const a = event.currentTarget;
	const listItemTag = a.dataset.listItemTag;

	if ( ! listItemTag ) {
		return;
	}

	props.recordEvent( 'home_quick_links_click', {
		task_name: listItemTag,
	} );

	if ( typeof props.onItemClick !== 'function' ) {
		return;
	}

	if ( ! props.onItemClick( listItemTag ) ) {
		event.preventDefault();
		return false;
	}
}

function getLinkTypeAndHref( item ) {
	let linkType;
	let href;

	switch ( item.type ) {
		case 'wc-admin':
			linkType = 'wc-admin';
			href = `admin.php?page=wc-admin&path=%2F${ item.path }`;
			break;
		case 'wp-admin':
			linkType = 'wp-admin';
			href = item.path;
			break;
		case 'wc-settings':
			linkType = 'wp-admin';
			href = `admin.php?page=wc-settings&tab=${ item.tab }`;
			break;
		default:
			linkType = 'external';
			href = item.href;
			break;
	}

	return {
		linkType,
		href,
	};
}

function getListItems( props ) {
	return getItems( props ).map( ( item ) => {
		return {
			title: item.title,
			before: <Icon icon={ item.icon } />,
			after: <Icon icon={ chevronRight } />,
			...getLinkTypeAndHref( item ),
			listItemTag: item.listItemTag,
			onClick: partial( handleOnItemClick, props ),
		};
	} );
}

const QuickLinks = ( props ) => {
	const listItems = getListItems( props );

	return (
		<Card size="large" className="woocommerce-quick-links">
			<CardHeader>
				<H>{ __( 'Store management', 'woocommerce-admin' ) }</H>
			</CardHeader>
			<CardBody>
				<List
					items={ listItems }
					className="woocommerce-quick-links__list"
				/>
			</CardBody>
		</Card>
	);
};

QuickLinks.defaultProps = {
	getSetting,
	recordEvent,
};

export default QuickLinks;

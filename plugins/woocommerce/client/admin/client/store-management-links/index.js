/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { Card, CardBody, CardHeader } from '@wordpress/components';
import {
	megaphone,
	box,
	brush,
	home,
	shipping,
	percent,
	payment,
	pencil,
} from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import './style.scss';
import { QuickLinkCategory } from './quick-link-category';
import { QuickLink } from './quick-link';
import { getAdminSetting } from '~/utils/admin-settings';

export function getLinkTypeAndHref( { path, tab = null, type, href = null } ) {
	return (
		{
			'wc-admin': {
				href: `admin.php?page=wc-admin&path=%2F${ path }`,
				linkType: 'wc-admin',
			},
			'wp-admin': {
				href: path,
				linkType: 'wp-admin',
			},
			'wc-settings': {
				href: `admin.php?page=wc-settings&tab=${ tab }`,
				linkType: 'wp-admin',
			},
		}[ type ] || {
			href,
			linkType: 'external',
		}
	);
}

export function getItemsByCategory( shopUrl ) {
	return [
		{
			title: __( 'Marketing & Merchandising', 'woocommerce' ),
			items: [
				{
					title: __( 'Marketing', 'woocommerce' ),
					link: getLinkTypeAndHref( {
						type: 'wc-admin',
						path: 'marketing',
					} ),
					icon: megaphone,
					listItemTag: 'marketing',
				},
				{
					title: __( 'Add products', 'woocommerce' ),
					link: getLinkTypeAndHref( {
						type: 'wp-admin',
						path: 'post-new.php?post_type=product',
					} ),
					icon: box,
					listItemTag: 'add-products',
				},
				{
					title: __( 'Personalize my store', 'woocommerce' ),
					link: getLinkTypeAndHref( {
						type: 'wp-admin',
						path: 'customize.php',
					} ),
					icon: brush,
					listItemTag: 'personalize-store',
				},
				{
					title: __( 'View my store', 'woocommerce' ),
					link: getLinkTypeAndHref( {
						type: 'external',
						href: shopUrl,
					} ),
					icon: home,
					listItemTag: 'view-store',
				},
			],
		},
		{
			title: __( 'Settings', 'woocommerce' ),
			items: [
				{
					title: __( 'Store details', 'woocommerce' ),
					link: getLinkTypeAndHref( {
						type: 'wc-settings',
						tab: 'general',
					} ),
					icon: pencil,
					listItemTag: 'edit-store-details',
				},
				{
					title: __( 'Payments', 'woocommerce' ),
					link: getLinkTypeAndHref( {
						type: 'wc-settings',
						tab: 'checkout',
					} ),
					icon: payment,
					listItemTag: 'payment-settings',
				},
				{
					title: __( 'Tax', 'woocommerce' ),
					link: getLinkTypeAndHref( {
						type: 'wc-settings',
						tab: 'tax',
					} ),
					icon: percent,
					listItemTag: 'tax-settings',
				},
				{
					title: __( 'Shipping', 'woocommerce' ),
					link: getLinkTypeAndHref( {
						type: 'wc-settings',
						tab: 'shipping',
					} ),
					icon: shipping,
					listItemTag: 'shipping-settings',
				},
			],
		},
	];
}

export const generateExtensionLinks = ( links ) => {
	return links.reduce( ( acc, { icon, href, title } ) => {
		const url = new URL( href, window.location.href );

		// We do not support extension links that take users away from the host.
		if ( url.origin === window.location.origin ) {
			acc.push( {
				icon,
				link: {
					href,
					linkType: 'wp-admin',
				},
				title,
				listItemTag: 'quick-links-extension-link',
			} );
		}

		return acc;
	}, [] );
};

export const StoreManagementLinks = () => {
	const shopUrl = getAdminSetting( 'shopUrl' );

	const extensionQuickLinks = generateExtensionLinks(
		/**
		 * An object defining an extension link.
		 *
		 * @typedef {Object} link
		 * @property {Node}   icon  Icon to render.
		 * @property {string} href  Url.
		 * @property {string} title Link title.
		 */

		/**
		 * Store Management extensions links
		 *
		 * @filter woocommerce_admin_homescreen_quicklinks
		 * @param {Array.<link>} links Array of links.
		 */
		applyFilters( 'woocommerce_admin_homescreen_quicklinks', [] )
	);

	const itemCategories = getItemsByCategory( shopUrl );

	const extensionCategory = {
		title: __( 'Extensions', 'woocommerce' ),
		items: extensionQuickLinks,
	};

	const categories = extensionQuickLinks.length
		? [ ...itemCategories, extensionCategory ]
		: itemCategories;

	return (
		<Card size="medium">
			<CardHeader size="medium">
				<Text variant="title.small" size="20" lineHeight="28px">
					{ __( 'Store management', 'woocommerce' ) }
				</Text>
			</CardHeader>
			<CardBody
				size="custom"
				className="woocommerce-store-management-links__card-body"
			>
				{ categories.map( ( category ) => {
					return (
						<QuickLinkCategory
							key={ category.title }
							title={ category.title }
						>
							{ category.items.map(
								( {
									icon,
									listItemTag,
									title,
									link: { href, linkType },
								} ) => (
									<QuickLink
										icon={ icon }
										key={ `${ title }_${ listItemTag }_${ href }` }
										title={ title }
										linkType={ linkType }
										href={ href }
										onClick={ () => {
											recordEvent(
												'home_quick_links_click',
												{
													task_name: listItemTag,
												}
											);
										} }
									/>
								)
							) }
						</QuickLinkCategory>
					);
				} ) }
			</CardBody>
		</Card>
	);
};

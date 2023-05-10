/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, createInterpolateElement } from '@wordpress/element';
import { TourKitTypes } from '@woocommerce/components';

export const getSteps = (): TourKitTypes.WooStep[] => {
	const lineBreak = createElement( 'br' );
	return [
		{
			referenceElements: {
				desktop: '#adminmenu a[href="admin.php?page=wc-addons"]',
			},
			focusElement: {
				desktop: '#adminmenu a[href="admin.php?page=wc-addons"]',
			},
			meta: {
				name: 'wc-addons-menu-item',
				heading: __(
					'Welcome to the WooCommerce Marketplace',
					'woocommerce'
				),
				descriptions: {
					desktop: createInterpolateElement(
						__(
							'Power up your store by adding extra functionality using extensions, find a fresh new look with themes, or integrate your store with other software and services.<br/><br/>The WooCommerce Marketplace is your go-to for all of the above, and the only place you’ll find products that have been reviewed and approved by the WooCommerce team.<br/><br/>Whether you’re looking to improve your store or grow your business, you can find a solution here. There are hundreds of options available, and new products are added regularly.<br/><br/>The WooCommerce Marketplace is also available at WooCommerce.com.',
							'woocommerce'
						),
						{
							br: lineBreak,
						}
					),
				},
			},
		},
		{
			referenceElements: {
				desktop: '.marketplace-header__search-form',
			},
			focusElement: {
				desktop: '.marketplace-header__search-form',
			},
			meta: {
				name: 'wc-addons-search',
				heading: __( 'Find exactly what you need', 'woocommerce' ),
				descriptions: {
					desktop: __(
						'Use the search box to find specific products or solutions.',
						'woocommerce'
					),
				},
			},
		},
		{
			referenceElements: {
				desktop: '#marketplace-current-section-dropdown',
			},
			focusElement: {
				desktop: '#marketplace-current-section-dropdown',
			},
			meta: {
				name: 'wc-addons-categories',
				heading: __( 'Browse for new ideas', 'woocommerce' ),
				descriptions: {
					desktop: createInterpolateElement(
						__(
							'Or browse all available products by category.',
							'woocommerce'
						),
						{
							br: lineBreak,
						}
					),
				},
			},
		},
		{
			referenceElements: {
				desktop: '.addon-product-group:first-child',
			},
			focusElement: {
				desktop: '.addon-product-group:first-child',
			},
			meta: {
				name: 'wc-addons-featured',
				heading: __( 'Learn more about products', 'woocommerce' ),
				descriptions: {
					desktop: createInterpolateElement(
						__(
							'Scroll down to see all available products for a search or selected category.<br/><br/>Click on any product to see more information about it, including features, requirements, and available documentation.',
							'woocommerce'
						),
						{
							br: lineBreak,
						}
					),
				},
			},
		},
		{
			referenceElements: {
				desktop: '.marketplace-header__tab-link_helper',
			},
			focusElement: {
				desktop: '.marketplace-header__tab-link_helper',
			},
			meta: {
				name: 'wc-addons-my-subscriptions',
				heading: __( 'Manage your purchases', 'woocommerce' ),
				descriptions: {
					desktop: createInterpolateElement(
						__(
							"Products purchased from the WooCommerce Marketplace can be managed in My Subscriptions, either here or on WooCommerce.com.<br/><br/>Every purchase is backed by our <a1>30-day money-back guarantee</a1>, and includes <a2>email and live chat support</a2>.<br/><br/>That's it! We hope the WooCommerce Marketplace helps you build the business of your dreams.",
							'woocommerce'
						),
						{
							a1: createElement(
								'a',
								{
									href: 'https://woocommerce.com/refund-policy/',
									'aria-label': __(
										'Refund policy',
										'woocommerce'
									),
								},
								__(
									'30-day money-back guarantee',
									'woocommerce'
								)
							),
							a2: createElement(
								'a',
								{
									href: 'https://woocommerce.com/my-account/create-a-ticket/',
									'aria-label': __(
										'Contact support',
										'woocommerce'
									),
								},
								__(
									'email and live chat support',
									'woocommerce'
								)
							),
							br: lineBreak,
						}
					),
				},
			},
		},
	];
};

/**
 * External dependencies
 */
import { TourKitTypes } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { createElement, createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { scrollPopperToVisibleAreaIfNeeded } from './utils';

export const getTourConfig = ( {
	closeHandler,
	onNextStepHandler,
	onPreviousStepHandler,
	autoScrollBlock,
}: {
	closeHandler: TourKitTypes.CloseHandler;
	onNextStepHandler: ( currentStepIndex: number ) => void;
	onPreviousStepHandler: ( currentStepIndex: number ) => void;
	autoScrollBlock: ScrollLogicalPosition;
} ): TourKitTypes.WooConfig => {
	const lineBreak = createElement( 'br' );

	let previousPopperTopPosition: number | null = null;
	let perviousPopperRef: unknown = null;
	const defaultPlacement = 'top-start';

	return {
		placement: defaultPlacement,
		options: {
			effects: {
				spotlight: {
					interactivity: {
						enabled: true,
						rootElementSelector: '.woocommerce.wc-addons-wrap',
					},
				},
				autoScroll: {
					behavior: 'auto',
					block: autoScrollBlock,
				},
			},
			popperModifiers: [
				{
					name: 'arrow',
					options: {
						padding: ( {
							popper,
						}: {
							popper: { width: number };
						} ) => {
							return {
								// Align the arrow to the left of the popper.
								right: popper.width - 34,
							};
						},
					},
				},
				{
					name: 'offset',
					options: {
						offset: [ 20, 20 ],
					},
				},
				{
					name: 'flip',
					options: {
						allowedAutoPlacements: [ 'right', 'bottom', 'top' ],
						fallbackPlacements: [ 'bottom-start', 'right' ],
						flipVariations: false,
						boundry: 'clippingParents',
					},
				},
				{
					name: 'inAppTourPopperModifications',
					enabled: true,
					phase: 'read',
					fn( { state, instance } ) {
						// 1. First modification - force `right` placement for items in admin menu.
						if ( perviousPopperRef !== state.elements.reference ) {
							const isAdminMenuItem = (
								state.elements.reference as HTMLElement
							 ).closest( '#adminmenu' );
							const desiredPlacement = isAdminMenuItem
								? 'right'
								: defaultPlacement;
							if ( state.placement !== desiredPlacement ) {
								instance.setOptions( {
									placement: desiredPlacement,
								} );
							}
						}

						// 2. Second modification - Try to make sure that the popper is visible once when
						// the next step is displayed.
						const popperBoundingRect =
							state.elements.popper.getBoundingClientRect();
						const arrowBoundingRect =
							state.elements.arrow?.getBoundingClientRect();
						const arrowHeight = arrowBoundingRect?.height || 0;

						// Try to make sure that the popper is visible if poppers' reference (step) changed and
						// if arrowHeight is not 0 (it means that popper's position hasn't been updated yet).
						// Also, change if popper's top position changed - the modifier can be called
						// multiple times for the same position.
						if (
							perviousPopperRef !== state.elements.reference &&
							arrowHeight !== 0 &&
							previousPopperTopPosition !== popperBoundingRect.top
						) {
							scrollPopperToVisibleAreaIfNeeded(
								popperBoundingRect
							);
							previousPopperTopPosition = popperBoundingRect.top;
							perviousPopperRef = state.elements.reference;
						}
					},
				},
			],
			callbacks: {
				onNextStep: onNextStepHandler,
				onPreviousStep: onPreviousStepHandler,
			},
		},
		steps: [
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
								'The WooCommerce Marketplace is the place to go to find vetted extensions, themes, and services for your store.<br/><br/>There are hundreds of options available, and new products are added regularly. So start here when you want to enhance your store or discover new ways to grow your business.<br/><br/>The WooCommerce Marketplace is also available at WooCommerce.com.',
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
							'You can use the search box to find specific products in the Marketplace.',
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
								'Or browse all available products by category.<br/><br/>The Featured category is regularly updated with new and noteworthy products.',
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
					heading: __(
						'Learn more about available products',
						'woocommerce'
					),
					descriptions: {
						desktop: createInterpolateElement(
							__(
								'All available products will be displayed here (scroll down to see them all), along with a summary about each product.<br/><br/>You can click on any product to see more information about it, including its features, requirements, and available documentation.',
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
								"Products purchased from the WooCommerce Marketplace can be managed in My Subscriptions, either here or on WooCommerce.com after logging in.<br/><br/>Every purchase is backed by our <a1>30-day money-back guarantee</a1> and includes <a2>email and live chat support</a2> from our dedicated Happiness Engineers.<br/><br/>That's it! We hope the Marketplace helps you build the business of your dreams. We’re rooting for you.",
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
		],
		closeHandler,
	};
};

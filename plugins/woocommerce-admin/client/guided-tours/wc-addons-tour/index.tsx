/**
 * External dependencies
 */
import {
	useEffect,
	useState,
	createInterpolateElement,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TourKit, TourKitTypes } from '@woocommerce/components';
import qs from 'qs';
import {
	Step,
	Options,
	Config,
	TourStepRendererProps,
} from '@automattic/tour-kit';

/**
 * Internal dependencies
 */
import { waitUntilElementTopNotChange } from '../add-product-tour/utils';

const getTourConfig = ( {
	closeHandler,
	onNextStepHandler,
	onPreviousStepHandler,
}: {
	closeHandler: TourKitTypes.CloseHandler;
	onNextStepHandler: ( currentStepIndex: number ) => void;
	onPreviousStepHandler: ( currentStepIndex: number ) => void;
} ): TourKitTypes.WooConfig => {
	return {
		placement: 'auto',
		options: {
			effects: {
				spotlight: {
					interactivity: {
						enabled: true,
						rootElementSelector: '.woocommerce.wc-addons-wrap',
					},
				},
				arrowIndicator: true,
				liveResize: {
					mutation: true,
					resize: true,
					rootElementSelector: '.woocommerce.wc-addons-wrap',
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
								br: <br />,
							}
						),
					},
				},
			},
			{
				referenceElements: {
					desktop: '#marketplace-current-section-dropdown',
				},
				focusElement: {
					desktop:
						'.marketplace-header__search-form, #marketplace-current-section-dropdown',
				},
				meta: {
					name: 'wc-addons-categories-and-search',
					heading: __( 'Find exactly what you need', 'woocommerce' ),
					descriptions: {
						desktop: createInterpolateElement(
							__(
								'You can use the search box to find specific products or browse all available products by category.<br/><br/>The Featured category is regularly updated with new and noteworthy products.',
								'woocommerce'
							),
							{
								br: <br />,
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
								br: <br />,
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
								a1: (
									<a
										href="https://woocommerce.com/refund-policy/"
										aria-label={ __(
											'Refund policy',
											'woocommerce'
										) }
									/>
								),
								a2: (
									<a
										href="https://woocommerce.com/my-account/create-a-ticket/"
										aria-label={ __(
											'Contact support',
											'woocommerce'
										) }
									/>
								),
								br: <br />,
							}
						),
					},
				},
			},
		],
		closeHandler,
	};
};

export const WCAddonsTour = () => {
	const [ showTour, setShowTour ] = useState< boolean >( false );
	const [ tourConfig, setTourConfig ] = useState< TourKitTypes.WooConfig >();

	const closeHandler = ( steps: Step[], currentStepIndex: number ) => {
		setShowTour( false );
		if ( steps.length - 1 === currentStepIndex ) {
			// TODO: Track Tour as completed
		} else {
			// TODO: Track Tour as dismissed
		}
	};

	const scrollForStep = ( stepIndex: number ) => {
		const step = tourConfig?.steps[ stepIndex ];
		if ( ! step ) {
			return;
		}

		const stepName = step?.meta?.name || '';
		if ( stepName === 'wc-addons-my-subscriptions' ) {
			document
				.querySelector( '.woocommerce.wc-addons-wrap' )
				?.scrollIntoView( {
					behavior: 'smooth',
					block: 'start',
				} );
		}
	};

	const onNextStepHandler = ( stepIndex: number ) => {
		const stepName = tourConfig?.steps[ stepIndex ]?.meta?.name || '';

		scrollForStep( stepIndex + 1 );

		// TODO: Track tour's step as completed
	};

	const onPreviousStepHandler = ( stepIndex: number ) => {
		const stepName = tourConfig?.steps[ stepIndex ]?.meta?.name || '';

		scrollForStep( stepIndex - 1 );
	};

	setTourConfig(
		getTourConfig( {
			closeHandler,
			onNextStepHandler,
			onPreviousStepHandler,
		} )
	);

	useEffect( () => {
		const query = qs.parse( window.location.search.slice( 1 ) );
		if ( query && query.tutorial === 'true' ) {
			const intervalId = waitUntilElementTopNotChange(
				tourConfig?.steps[ 0 ].referenceElements?.desktop || '',
				() => {
					setShowTour( true );

					// TODO: track the tour is started
				},
				500
			);
			return () => clearInterval( intervalId );
		}
		// only run once
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	if ( ! showTour || ! tourConfig ) {
		return null;
	}

	return (
		<>
			<TourKit config={ tourConfig } />
		</>
	);
};

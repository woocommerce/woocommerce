/**
 * External dependencies
 */
import { TourKit, TourKitTypes } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	useLayoutEffect,
	useEffect,
	useState,
	useRef,
	createPortal,
} from '@wordpress/element';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
/**
 * Internal dependencies
 */
import { getCountryCode } from '~/dashboard/utils';

const REVIEWED_DEFAULTS_OPTION =
	'woocommerce_admin_reviewed_default_shipping_zones';

const CREATED_DEFAULTS_OPTION =
	'woocommerce_admin_created_default_shipping_zones';

const FLOATER_WRAPPER_CLASS =
	'woocommerce-settings-shipping-tour-floater-wrapper';

const FLOATER_CLASS =
	'woocommerce-settings-smart-defaults-shipping-tour-floater';

const SHIPPING_ZONES_SETTINGS_TABLE_CLASS = 'table.wc-shipping-zones';

const WCS_LINK_SELECTOR = 'a[href*="woocommerce-services-settings"]';

const SHIPPING_RECOMMENDATIONS_SELECTOR =
	'div.woocommerce-recommended-shipping-extensions';

const useShowShippingTour = () => {
	const {
		hasCreatedDefaultShippingZones,
		hasReviewedDefaultShippingOptions,
		businessCountry,
		isLoading,
	} = useSelect( ( select ) => {
		const { hasFinishedResolution, getOption } =
			select( OPTIONS_STORE_NAME );

		return {
			isLoading:
				! hasFinishedResolution( 'getOption', [
					CREATED_DEFAULTS_OPTION,
				] ) &&
				! hasFinishedResolution( 'getOption', [
					REVIEWED_DEFAULTS_OPTION,
				] ) &&
				! hasFinishedResolution( 'getOption', [
					'woocommerce_default_country',
				] ),
			hasCreatedDefaultShippingZones:
				getOption( CREATED_DEFAULTS_OPTION ) === 'yes',
			hasReviewedDefaultShippingOptions:
				getOption( REVIEWED_DEFAULTS_OPTION ) === 'yes',
			businessCountry: getCountryCode(
				getOption( 'woocommerce_default_country' ) as string
			),
		};
	} );

	return {
		isLoading,
		show:
			window.wcAdminFeatures[ 'shipping-setting-tour' ] &&
			! isLoading &&
			hasCreatedDefaultShippingZones &&
			! hasReviewedDefaultShippingOptions,
		isUspsDhlEligible: businessCountry === 'US',
	};
};

type NonEmptySelectorArray = readonly [ string, ...string[] ];

const computeDims = ( elementsSelectors: NonEmptySelectorArray ) => {
	const rects = elementsSelectors.map( ( elementSelector ) => {
		const rect = document
			?.querySelector( elementSelector )
			?.getBoundingClientRect();

		if ( ! rect ) {
			throw new Error(
				'Shipping tour: Couldn’t find element with selector: ' +
					elementSelector
			);
		}
		return rect;
	} );

	const originCoords = document
		.querySelector( `.${ FLOATER_WRAPPER_CLASS }` )
		?.getBoundingClientRect() || { top: 0, left: 0 };

	const top = Math.min( ...rects.map( ( rect ) => rect.top ) );
	const left = Math.min( ...rects.map( ( rect ) => rect.left ) );
	const right = Math.max( ...rects.map( ( rect ) => rect.right ) );
	const bottom = Math.max( ...rects.map( ( rect ) => rect.bottom ) );
	const width = right - left;
	const height = bottom - top;

	// offset top and left from origin
	const topOffset = top - originCoords.top;
	const leftOffset = left - originCoords.left;

	return { left: leftOffset, top: topOffset, width, height };
};

const TourFloater = ( { dims }: { dims: Partial< DOMRect > } ) => {
	return (
		<div
			style={ {
				position: 'relative',
				pointerEvents: 'none',
				...dims,
			} }
			className={ FLOATER_CLASS }
		></div>
	);
};

// this is defines the elements to be spotlit for each step
const spotlitElementsSelectors: Array< NonEmptySelectorArray > = [
	[
		// just use bottom right element and top left element instead of all rects
		// top left = table header cell for sort handles
		'th.wc-shipping-zone-sort',
		// bottom right = worldwide region cell
		'tfoot.wc-shipping-zone-worldwide tr > td.wc-shipping-zone-region',
	],
	[
		// selectors for rightmost column (shipping methods)
		'th.wc-shipping-zone-methods',
		'tfoot.wc-shipping-zone-worldwide tr > td.wc-shipping-zone-methods',
	],
];

const TourFloaterWrapper = ( { step }: { step: number } ) => {
	const thisRef = useRef< HTMLDivElement >( null );
	useLayoutEffect( () => {
		// this moves the element to the correct place which is right before the table element
		if ( thisRef.current?.parentElement ) {
			thisRef.current.parentElement.insertBefore(
				thisRef.current,
				document.querySelector( 'table.wc-shipping-zones' )
			);
		}
	}, [] );

	const currentStepSelectors =
		spotlitElementsSelectors[ step ] ??
		spotlitElementsSelectors[ spotlitElementsSelectors.length - 1 ];

	const [ dims, setDims ] = useState( computeDims( currentStepSelectors ) );
	useEffect( () => {
		setDims( computeDims( currentStepSelectors ) );
		const observer = new ResizeObserver( () => {
			setDims( computeDims( currentStepSelectors ) );
		} );

		const shippingSettingsTableElement = document.querySelector(
			SHIPPING_ZONES_SETTINGS_TABLE_CLASS
		);

		if ( ! shippingSettingsTableElement ) {
			throw new Error(
				'Shipping tour: Couldn’t find shipping settings table element with selector: ' +
					SHIPPING_ZONES_SETTINGS_TABLE_CLASS
			);
		}

		observer.observe( shippingSettingsTableElement );

		return () => {
			observer.disconnect();
		};
	}, [ currentStepSelectors ] );

	const shippingSettingsTableParentElement = document.querySelector(
		SHIPPING_ZONES_SETTINGS_TABLE_CLASS
	)?.parentElement;

	if ( ! shippingSettingsTableParentElement ) {
		throw new Error(
			'Shipping tour: Couldn’t find shipping settings table parent element with selector: ' +
				SHIPPING_ZONES_SETTINGS_TABLE_CLASS
		);
	}
	/**
	 *  use ReactDOM.createPortal to inject our element into non-React controlled DOM
	 *  unfortunately createPortal uses .appendChild which puts it in the wrong place,
	 *  we want it to be before the table
	 */
	return createPortal(
		<div
			ref={ thisRef }
			className={ FLOATER_WRAPPER_CLASS }
			style={ { position: 'absolute' } }
		>
			{ <TourFloater dims={ dims } /> }
		</div>,
		shippingSettingsTableParentElement
	);
};

export const ShippingTour: React.FC< {
	showShippingRecommendationsStep: boolean;
} > = ( { showShippingRecommendationsStep } ) => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { show: showTour, isUspsDhlEligible } = useShowShippingTour();
	const [ step, setStepNumber ] = useState( 0 );
	const { createNotice } = useDispatch( 'core/notices' );

	const tourConfig: TourKitTypes.WooConfig = {
		placement: 'auto',
		options: {
			effects: {
				spotlight: {
					interactivity: {
						enabled: false,
					},
				},
				liveResize: {
					mutation: true,
					resize: true,
				},
				autoScroll: true,
			},
			callbacks: {
				onNextStep: ( newStepIndex ) => {
					setStepNumber( newStepIndex );
					recordEvent( 'walkthrough_settings_shipping_next_click', {
						step_name:
							tourConfig.steps[ newStepIndex - 1 ].meta.name,
					} );
				},
				onPreviousStep: ( newStepIndex ) => {
					setStepNumber( newStepIndex );
					recordEvent( 'walkthrough_settings_shipping_back_click', {
						step_name:
							tourConfig.steps[ newStepIndex + 1 ].meta.name,
					} );
				},
			},
		},
		steps: [
			{
				referenceElements: {
					desktop: `.${ FLOATER_CLASS }`,
				},
				meta: {
					name: 'shipping-zones',
					heading: __( 'Shipping zones', 'woocommerce' ),
					descriptions: {
						desktop: (
							<>
								<span>
									{ __(
										'Specify the areas you’d like to ship to! Give each zone a name, then list the regions you’d like to include. Your regions can be as specific as a zip code or as broad as a country. Shoppers will only see the methods available in their region.',
										'woocommerce'
									) }
								</span>
								<br />
								<span>
									{ __(
										'We’ve added some shipping zones to get you started — you can manage them by selecting Edit or Delete.',
										'woocommerce'
									) }
								</span>
							</>
						),
					},
				},
			},
			{
				referenceElements: {
					desktop: `.${ FLOATER_CLASS }`,
				},
				meta: {
					name: 'shipping-methods',
					heading: __( 'Shipping methods', 'woocommerce' ),
					descriptions: {
						desktop: (
							<>
								<span>
									{ __(
										'Add one or more shipping methods you’d like to offer to shoppers in your zones.',
										'woocommerce'
									) }
								</span>
								<br />
								<span>
									{ __(
										'For example, we’ve added the “Free shipping” method for shoppers in your country. You can edit, add to, or remove shipping methods by selecting Edit or Delete.',
										'woocommerce'
									) }
								</span>
							</>
						),
					},
				},
			},
		],
		closeHandler: async ( steps, stepIndex, source ) => {
			const update = await updateOptions( {
				[ REVIEWED_DEFAULTS_OPTION ]: 'yes',
			} );

			if ( ! update.success ) {
				createNotice(
					'error',
					__(
						'There was a problem marking the shipping tour as completed.',
						'woocommerce'
					)
				);
				recordEvent(
					'walkthrough_settings_shipping_updated_option_error'
				);
			}

			if ( source === 'close-btn' ) {
				recordEvent( 'walkthrough_settings_shipping_dismissed', {
					step_name: steps[ stepIndex ].meta.name,
				} );
			} else if ( source === 'done-btn' ) {
				recordEvent( 'walkthrough_settings_shipping_completed' );
			}
		},
	};

	const isWcsSectionPresent = document.querySelector( WCS_LINK_SELECTOR );

	if ( isWcsSectionPresent && isUspsDhlEligible ) {
		tourConfig.steps.push( {
			referenceElements: {
				desktop: WCS_LINK_SELECTOR,
			},
			meta: {
				name: 'woocommerce-shipping',
				heading: __( 'WooCommerce Shipping', 'woocommerce' ),
				descriptions: {
					desktop: __(
						'Print USPS and DHL labels straight from your Woo dashboard and save on shipping thanks to discounted rates. You can manage WooCommerce Shipping in this section.',
						'woocommerce'
					),
				},
			},
		} );
	}

	if ( showShippingRecommendationsStep ) {
		tourConfig.steps.push( {
			referenceElements: {
				desktop: SHIPPING_RECOMMENDATIONS_SELECTOR,
			},
			meta: {
				name: 'shipping-recommendations',
				heading: __( 'WooCommerce Shipping', 'woocommerce' ),
				descriptions: {
					desktop: __(
						'If you’d like to speed up your process and print your shipping label straight from your Woo dashboard, WooCommerce Shipping may be for you! ',
						'woocommerce'
					),
				},
			},
		} );
	}

	useEffect( () => {
		if ( showTour ) {
			recordEvent( 'walkthrough_settings_shipping_view' );
		}
	}, [ showTour ] );

	if ( showTour ) {
		return (
			<div>
				<TourFloaterWrapper step={ step } />
				<TourKit config={ tourConfig }></TourKit>
			</div>
		);
	}

	return null;
};

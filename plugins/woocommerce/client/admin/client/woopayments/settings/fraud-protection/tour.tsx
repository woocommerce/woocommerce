/**
 * External dependencies
 */
import {
	createInterpolateElement,
	useCallback,
	useEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TourKit, TourKitTypes } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { saveOption } from '../data/actions';
import { useGetSettings, useSettings } from '../data/hooks';

type SettingsRecord = Record< string, unknown >;

const asSettingsRecord = ( value: unknown ): SettingsRecord =>
	value && typeof value === 'object' ? ( value as SettingsRecord ) : {};

const getIsWelcomeTourDismissed = ( settings: SettingsRecord ) => {
	const fraudProtection = asSettingsRecord( settings.fraud_protection );

	return fraudProtection.is_welcome_tour_dismissed === true;
};

const getTourReferenceElement = () =>
	document.getElementById( 'fraud-protection-card-options' ) ??
	document.getElementById( 'fraud-protection' );

const tourSteps: TourKitTypes.WooStep[] = [
	{
		referenceElements: {
			desktop: '#fraud-protection',
		},
		focusElement: {
			desktop: '#woopayments-fraud-protection-basic',
		},
		meta: {
			name: 'enhanced-fraud-protection',
			heading: __( 'Enhanced fraud protection', 'woocommerce' ),
			descriptions: {
				desktop: __(
					'You can choose a level of protection for screening incoming transactions. Screened transactions will be automatically blocked by your customized fraud filters.',
					'woocommerce'
				),
			},
			primaryButton: {
				text: __( "See what's new", 'woocommerce' ),
			},
		},
	},
	{
		referenceElements: {
			desktop: '#fraud-protection-card-options',
		},
		focusElement: {
			desktop: '#woopayments-fraud-protection-basic',
		},
		meta: {
			name: 'choose-your-filter-level',
			heading: __( 'Choose your filter level', 'woocommerce' ),
			descriptions: {
				desktop: __(
					"Choose how you'd like to screen incoming transactions using our Basic or Advanced options.",
					'woocommerce'
				),
			},
		},
	},
	{
		referenceElements: {
			desktop: '#woopayments-fraud-protection-advanced',
		},
		focusElement: {
			desktop: '#woopayments-fraud-protection-advanced',
		},
		meta: {
			name: 'take-more-control',
			heading: __( 'Take more control', 'woocommerce' ),
			descriptions: {
				desktop: __(
					'Choose Advanced settings for full control over each filter. You can enable and configure filters to block risky transactions.',
					'woocommerce'
				),
			},
		},
	},
	{
		referenceElements: {
			desktop: '#fraud-protection',
		},
		focusElement: {
			desktop: '#woopayments-fraud-protection-advanced',
		},
		meta: {
			name: 'review-blocked-transactions',
			heading: __( 'Review blocked transactions', 'woocommerce' ),
			descriptions: {
				desktop: createInterpolateElement(
					__(
						"Payments that have been blocked by a risk filter will appear under the blocked tab in <strong>Payments > Transactions</strong>. We'll let you know why each payment was blocked so you can determine if you need to adjust your risk filters.",
						'woocommerce'
					),
					{
						strong: <strong />,
					}
				),
			},
			primaryButton: {
				text: __( 'Got it', 'woocommerce' ),
			},
		},
	},
];

const prefersReducedMotion = () =>
	typeof window.matchMedia === 'function' &&
	window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

const getTourOptions = (
	onMinimize: NonNullable<
		TourKitTypes.WooOptions[ 'callbacks' ]
	>[ 'onMinimize' ]
): TourKitTypes.WooConfig[ 'options' ] => ( {
	effects: {
		arrowIndicator: true,
		spotlight: { styles: { padding: 8 } },
		autoScroll: {
			behavior: prefersReducedMotion() ? 'auto' : 'smooth',
			block: 'nearest',
		},
		liveResize: {
			mutation: true,
			resize: true,
			rootElementSelector: '#wpwrap',
		},
	},
	callbacks: {
		onMinimize,
	},
	popperModifiers: [
		{
			name: 'offset',
			options: {
				offset: [ 0, 20 ],
			},
		},
	],
} );

export const FraudProtectionTour = () => {
	const settings = asSettingsRecord( useGetSettings() );
	const { isLoading } = useSettings();
	const isSavingDismissal = useRef( false );
	const [ isDismissed, setIsDismissed ] = useState( () =>
		getIsWelcomeTourDismissed( settings )
	);
	const [ showTour, setShowTour ] = useState( false );
	const [ tourKey, setTourKey ] = useState( 0 );

	useEffect( () => {
		setIsDismissed( getIsWelcomeTourDismissed( settings ) );
	}, [ settings ] );

	useEffect( () => {
		const reference = getTourReferenceElement();

		if (
			isDismissed ||
			isLoading ||
			showTour ||
			! reference ||
			typeof window.IntersectionObserver === 'undefined'
		) {
			return;
		}

		const observer = new window.IntersectionObserver(
			( [ entry ] ) => {
				if ( entry?.isIntersecting ) {
					setShowTour( true );
					observer.disconnect();
				}
			},
			{ threshold: 1 }
		);

		observer.observe( reference );

		return () => observer.disconnect();
	}, [ isDismissed, isLoading, showTour ] );

	const dismissTour = useCallback( ( source: string ) => {
		if ( isSavingDismissal.current ) {
			return;
		}

		isSavingDismissal.current = true;
		void Promise.resolve(
			saveOption( 'wcpay_fraud_protection_welcome_tour_dismissed', true )
		).then( ( result ) => {
			if ( typeof result === 'undefined' ) {
				isSavingDismissal.current = false;
				setTourKey( ( current ) => current + 1 );
				return;
			}

			setIsDismissed( true );
			setShowTour( false );
			recordEvent(
				source === 'done-btn'
					? 'wcpay_fraud_protection_tour_clicked_through'
					: 'wcpay_fraud_protection_tour_abandoned'
			);
		} );
	}, [] );

	const handleTourEnd: TourKitTypes.CloseHandler = (
		_steps,
		_currentStepIndex,
		element
	) => {
		dismissTour( element );
	};

	if ( ! showTour ) {
		return null;
	}

	const config: TourKitTypes.WooConfig = {
		steps: tourSteps,
		options: getTourOptions( () => dismissTour( 'minimize' ) ),
		closeHandler: handleTourEnd,
	};

	return <TourKit key={ tourKey } config={ config } />;
};

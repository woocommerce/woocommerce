/**
 * External dependencies
 */
import { Gridicon } from '@automattic/components';
import { Button, Placeholder, SelectControl } from '@wordpress/components';
import { paymentSettingsStore } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import React, {
	useState,
	lazy,
	Suspense,
	useCallback,
	useEffect,
} from '@wordpress/element';
import {
	unstable_HistoryRouter as HistoryRouter,
	Route,
	Routes,
	useLocation,
} from 'react-router-dom';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';
import { getAdminLink } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { Header } from './components/header/header';
import { BackButton } from './components/buttons/back-button';
import { ListPlaceholder } from '~/settings-payments/components/list-placeholder';
import {
	getWooPaymentsTestDriveAccountLink,
	getWooPaymentsFromProviders,
} from '~/settings-payments/utils';
import './settings-payments-main.scss';

/**
 * Lazy-loaded chunk for the main settings page of payment gateways.
 */
const SettingsPaymentsMainChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-main" */ './settings-payments-main'
		)
);

/**
 * Lazy-loaded chunk for the recommended payment methods settings page.
 */
const SettingsPaymentsMethodsChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-methods" */ './settings-payments-methods'
		)
);

/**
 * Lazy-loaded chunk for the offline payment gateways settings page.
 */
const SettingsPaymentsOfflineChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-offline" */ './settings-payments-offline'
		)
);

/**
 * Lazy-loaded chunk for the WooPayments settings page.
 */
const SettingsPaymentsWooPaymentsChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-woocommerce-payments" */ './settings-payments-woopayments'
		)
);

const SettingsPaymentsBacsChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-bacs" */ './offline/settings-payments-bacs'
		)
);

const SettingsPaymentsCodChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-cod" */ './offline/settings-payments-cod'
		)
);

const SettingsPaymentsChequeChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-cheque" */ './offline/settings-payments-cheque'
		)
);

/**
 * Hides or displays the WooCommerce navigation tab based on the provided display style.
 */
const hideWooCommerceNavTab = ( display: string ) => {
	const externalElement = document.querySelector< HTMLElement >(
		'.woo-nav-tab-wrapper'
	);

	// Add the 'hidden' class to hide the element.
	if ( externalElement ) {
		externalElement.style.display = display;
	}
};

/**
 * Renders the main payment settings page with a fallback while loading.
 */
const SettingsPaymentsMain = () => {
	const location = useLocation();

	useEffect( () => {
		if ( location.pathname === '' ) {
			hideWooCommerceNavTab( 'flex' );
		}
	}, [ location ] );
	return (
		<>
			<Suspense
				fallback={
					<>
						<div className="settings-payments-main__container">
							<div className="settings-payment-gateways">
								<div className="settings-payment-gateways__header">
									<div className="settings-payment-gateways__header-title">
										{ __(
											'Payment providers',
											'woocommerce'
										) }
									</div>
									<div className="settings-payment-gateways__header-select-container">
										<SelectControl
											className="woocommerce-select-control__country"
											prefix={ __(
												'Business location :',
												'woocommerce'
											) }
											// eslint-disable-next-line @typescript-eslint/ban-ts-comment
											// @ts-ignore placeholder prop exists
											placeholder={ '' }
											label={ '' }
											options={ [] }
											onChange={ () => {} }
										/>
									</div>
								</div>
								<ListPlaceholder rows={ 5 } />
							</div>
							<div className="other-payment-gateways">
								<div className="other-payment-gateways__header">
									<div className="other-payment-gateways__header__title">
										<span>
											{ __(
												'Other payment options',
												'woocommerce'
											) }
										</span>
										<>
											<div className="other-payment-gateways__header__title__image-placeholder" />
											<div className="other-payment-gateways__header__title__image-placeholder" />
											<div className="other-payment-gateways__header__title__image-placeholder" />
										</>
									</div>
									<Button
										variant={ 'link' }
										onClick={ () => {} }
										aria-expanded={ false }
									>
										<Gridicon icon="chevron-down" />
									</Button>
								</div>
							</div>
						</div>
					</>
				}
			>
				<SettingsPaymentsMainChunk />
			</Suspense>
		</>
	);
};

/**
 * Renders the recommended payment methods settings page with a fallback while loading.
 */
export const SettingsPaymentsMethods = () => {
	const location = useLocation();
	const [ paymentMethodsState, setPaymentMethodsState ] = useState< {
		[ key: string ]: boolean;
	} >( {} );
	const [ isCompleted, setIsCompleted ] = useState( false );
	const { providers } = useSelect( ( select ) => {
		return {
			isFetching: select( paymentSettingsStore ).isFetching(),
			providers:
				select( paymentSettingsStore ).getPaymentProviders(
					window.wcSettings?.admin?.woocommerce_payments_nox_profile
						?.business_country_code || null
				) || [],
		};
	}, [] );

	// Retrieve the WooPayments gateway.
	const wooPayments = getWooPaymentsFromProviders( providers );

	const onPaymentMethodsContinueClick = useCallback( () => {
		// Record the event along with payment methods selected.
		recordEvent( 'wcpay_settings_payment_methods_continue', {
			displayed_payment_methods:
				Object.keys( paymentMethodsState ).join( ', ' ),
			selected_payment_methods: Object.keys( paymentMethodsState )
				.filter(
					( paymentMethod ) => paymentMethodsState[ paymentMethod ]
				)
				.join( ', ' ),
			deselected_payment_methods: Object.keys( paymentMethodsState )
				.filter(
					( paymentMethod ) => ! paymentMethodsState[ paymentMethod ]
				)
				.join( ', ' ),
			business_country:
				window.wcSettings?.admin?.woocommerce_payments_nox_profile
					?.business_country_code ?? 'unknown',
		} );

		setIsCompleted( true );

		// Get the onboarding URL or fallback to the test drive account link.
		const onboardUrl =
			wooPayments?.onboarding?._links?.onboard?.href ||
			getWooPaymentsTestDriveAccountLink();

		// Combine the onboard URL with the query string and redirect to the onboard URL.
		window.location.href =
			onboardUrl +
			'&capabilities=' +
			encodeURIComponent( JSON.stringify( paymentMethodsState ) );
	}, [ paymentMethodsState, wooPayments ] );

	useEffect( () => {
		window.scrollTo( 0, 0 ); // Scrolls to the top-left corner of the page.

		if ( location.pathname === '/payment-methods' ) {
			hideWooCommerceNavTab( 'none' );
			recordEvent( 'wcpay_settings_payment_methods_pageview' );
		}
	}, [ location ] );

	return (
		<>
			<div className="woocommerce-layout__header woocommerce-recommended-payment-methods">
				<div className="woocommerce-layout__header-wrapper">
					<BackButton
						href={ getNewPath( {}, '' ) }
						title={ __(
							'Return to payments settings',
							'woocommerce'
						) }
						isRoute={ true }
						from={ 'woopayments_payment_methods' }
					/>
					<h1 className="components-truncate components-text woocommerce-layout__header-heading woocommerce-layout__header-left-align">
						<span className="woocommerce-settings-payments-header__title">
							{ __(
								'Choose your payment methods',
								'woocommerce'
							) }
						</span>
					</h1>
					<Button
						className="components-button is-primary"
						onClick={ onPaymentMethodsContinueClick }
						isBusy={ isCompleted }
						disabled={ isCompleted }
					>
						{ __( 'Continue', 'woocommerce' ) }
					</Button>
					<div className="woocommerce-settings-payments-header__description">
						{ __(
							"Select which payment methods you'd like to offer to your shoppers. You can update these here at any time.",
							'woocommerce'
						) }
					</div>
				</div>
			</div>
			<Suspense
				fallback={
					<>
						<div className="settings-payments-recommended__container">
							<div className="settings-payment-gateways">
								<ListPlaceholder
									rows={ 3 }
									hasDragIcon={ false }
								/>
							</div>
						</div>
					</>
				}
			>
				<SettingsPaymentsMethodsChunk
					paymentMethodsState={ paymentMethodsState }
					setPaymentMethodsState={ setPaymentMethodsState }
				/>
			</Suspense>
		</>
	);
};

/**
 * Wraps the main payment settings and payment methods settings pages.
 */
export const SettingsPaymentsMainWrapper = () => {
	return (
		<>
			<Header
				title={ __( 'Settings', 'woocommerce' ) }
				context={ 'wc_settings_payments__main' }
			/>
			<HistoryRouter history={ getHistory() }>
				<Routes>
					<Route
						path="/payment-methods"
						element={ <SettingsPaymentsMethods /> }
					/>
					<Route path="/*" element={ <SettingsPaymentsMain /> } />
				</Routes>
			</HistoryRouter>
		</>
	);
};

/**
 * Wraps the offline payment gateways settings page.
 */
export const SettingsPaymentsOfflineWrapper = () => {
	return (
		<>
			<Header
				title={ __( 'Take offline payments', 'woocommerce' ) }
				backLink={ getAdminLink(
					'admin.php?page=wc-settings&tab=checkout'
				) }
				context={ 'wc_settings_payments__offline_pms' }
			/>
			<Suspense
				fallback={
					<>
						<div className="settings-payments-offline__container">
							<div className="settings-payment-gateways">
								<div className="settings-payment-gateways__header">
									<div className="settings-payment-gateways__header-title">
										{ __(
											'Payment methods',
											'woocommerce'
										) }
									</div>
								</div>
								<ListPlaceholder rows={ 3 } />
							</div>
						</div>
					</>
				}
			>
				<SettingsPaymentsOfflineChunk />
			</Suspense>
		</>
	);
};

/**
 * Wraps the WooPayments settings page.
 */
export const SettingsPaymentsWooPaymentsWrapper = () => {
	return (
		<>
			<Header
				title={ __( 'Settings', 'woocommerce' ) }
				context={ 'wc_settings_payments__woopayments' }
			/>
			<Suspense fallback={ <div>Loading WooPayments settings...</div> }>
				<SettingsPaymentsWooPaymentsChunk />
			</Suspense>
		</>
	);
};

export const SettingsPaymentsBacsWrapper = () => {
	return (
		<>
			<Header
				title={ __( 'Direct bank transfer', 'woocommerce' ) }
				backLink={ getAdminLink(
					'admin.php?page=wc-settings&tab=checkout&section=offline'
				) }
				context={ 'wc_settings_payments__offline_pms_bacs' }
			/>
			<Suspense
				fallback={
					<>
						<div className="settings-payments-bacs__container">
							<div className="settings-payment-gateways">
								<div className="settings-payment-gateways__header">
									<div className="settings-payment-gateways__header-title">
										{ __(
											'Direct bank transfer',
											'woocommerce'
										) }
									</div>
								</div>
								<Placeholder />
							</div>
						</div>
					</>
				}
			>
				<SettingsPaymentsBacsChunk />
			</Suspense>
		</>
	);
};

export const SettingsPaymentsCodWrapper = () => {
	return (
		<>
			<Header
				title={ __( 'Cash on delivery', 'woocommerce' ) }
				backLink={ getAdminLink(
					'admin.php?page=wc-settings&tab=checkout&section=offline'
				) }
				context={ 'wc_settings_payments__offline_pms_cod' }
			/>
			<Suspense
				fallback={
					<>
						<div className="settings-payments-cod__container">
							<div className="settings-payment-gateways">
								<div className="settings-payment-gateways__header">
									<div className="settings-payment-gateways__header-title">
										{ __(
											'Cash on delivery',
											'woocommerce'
										) }
									</div>
								</div>
								<Placeholder />
							</div>
						</div>
					</>
				}
			>
				<SettingsPaymentsCodChunk />
			</Suspense>
		</>
	);
};

export const SettingsPaymentsChequeWrapper = () => {
	return (
		<>
			<Header
				title={ __( 'Check payments', 'woocommerce' ) }
				backLink={ getAdminLink(
					'admin.php?page=wc-settings&tab=checkout&section=offline'
				) }
				context={ 'wc_settings_payments__offline_pms_cheque' }
			/>
			<Suspense
				fallback={
					<>
						<div className="settings-payments-cheque__container">
							<div className="settings-payment-gateways">
								<div className="settings-payment-gateways__header">
									<div className="settings-payment-gateways__header-title">
										{ __(
											'Check payments',
											'woocommerce'
										) }
									</div>
								</div>
								<Placeholder />
							</div>
						</div>
					</>
				}
			>
				<SettingsPaymentsChequeChunk />
			</Suspense>
		</>
	);
};

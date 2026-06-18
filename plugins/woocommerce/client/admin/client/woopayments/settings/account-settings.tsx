/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getWooPaymentsAccountSettings } from './api';
import type { WooPaymentsAccount, WooPaymentsAccountResponse } from './types';
import './style.scss';

const PROVIDER_NAME = 'WooPayments';

const getSettingsTitle = () =>
	sprintf(
		/* translators: %s: Payment provider name. */
		__( '%s settings', 'woocommerce' ),
		PROVIDER_NAME
	);

const getLoadingText = () =>
	sprintf(
		/* translators: %s: Payment provider name. */
		__( 'Loading %s account…', 'woocommerce' ),
		PROVIDER_NAME
	);

const getSetupTitle = () =>
	sprintf(
		/* translators: %s: Payment provider name. */
		__( 'Set up %s', 'woocommerce' ),
		PROVIDER_NAME
	);

const getErrorTitle = () =>
	sprintf(
		/* translators: %s: Payment provider name. */
		__( 'Unable to load %s account', 'woocommerce' ),
		PROVIDER_NAME
	);

const getAccountModeLabel = ( account: WooPaymentsAccount ) => {
	if ( account.test_drive ) {
		return __( 'Test drive', 'woocommerce' );
	}

	if ( account.sandbox ) {
		return __( 'Sandbox', 'woocommerce' );
	}

	if ( account.live ) {
		return __( 'Live', 'woocommerce' );
	}

	if ( account.test_mode || account.mode === 'test' ) {
		return __( 'Test mode', 'woocommerce' );
	}

	if ( account.mode === 'live' ) {
		return __( 'Live', 'woocommerce' );
	}

	return account.mode;
};

const formatCurrencyCode = ( currencyCode: string ) => {
	if ( ! currencyCode ) {
		return '-';
	}

	return currencyCode.toUpperCase();
};

const getErrorMessage = ( error: unknown ) => {
	if ( error instanceof Error && error.message ) {
		return error.message;
	}

	if (
		error &&
		typeof error === 'object' &&
		'message' in error &&
		typeof error.message === 'string'
	) {
		return error.message;
	}

	return __( 'Something went wrong. Please try again.', 'woocommerce' );
};

type ResultHeadingRef = ( node: HTMLHeadingElement | null ) => void;

const AccountDetails = ( {
	account,
	headingRef,
}: {
	account: WooPaymentsAccount;
	headingRef?: ResultHeadingRef;
} ) => {
	const isReady = account.working;
	const canProcessPayments = account.can_process_payments;

	return (
		<section
			className="woopayments-account-settings__section"
			aria-labelledby="woopayments-account-settings-connected-heading"
		>
			<div className="woopayments-account-settings__section-header">
				<h2
					id="woopayments-account-settings-connected-heading"
					ref={ headingRef }
					tabIndex={ -1 }
				>
					{ __( 'Connected account', 'woocommerce' ) }
				</h2>
				<span className="woopayments-account-settings__badge">
					{ getAccountModeLabel( account ) }
				</span>
			</div>
			<dl className="woopayments-account-settings__details">
				<div className="woopayments-account-settings__detail">
					<dt>{ __( 'Account ID', 'woocommerce' ) }</dt>
					<dd>{ account.id }</dd>
				</div>
				<div className="woopayments-account-settings__detail">
					<dt>{ __( 'Default currency', 'woocommerce' ) }</dt>
					<dd>{ formatCurrencyCode( account.default_currency ) }</dd>
				</div>
				<div className="woopayments-account-settings__detail">
					<dt>{ __( 'Readiness', 'woocommerce' ) }</dt>
					<dd>
						{ isReady
							? __( 'Payments ready', 'woocommerce' )
							: __( 'Payments need attention', 'woocommerce' ) }
					</dd>
				</div>
				<div className="woopayments-account-settings__detail">
					<dt>{ __( 'Processing', 'woocommerce' ) }</dt>
					<dd>
						{ canProcessPayments
							? __( 'Can process payments', 'woocommerce' )
							: __( 'Cannot process payments', 'woocommerce' ) }
					</dd>
				</div>
			</dl>
		</section>
	);
};

const SetupState = ( {
	setupUrl,
	headingRef,
}: {
	setupUrl?: string;
	headingRef?: ResultHeadingRef;
} ) => (
	<section
		className="woopayments-account-settings__section"
		aria-labelledby="woopayments-account-settings-setup-heading"
	>
		<h2
			id="woopayments-account-settings-setup-heading"
			ref={ headingRef }
			tabIndex={ -1 }
		>
			{ getSetupTitle() }
		</h2>
		<p>
			{ __(
				'Connect an account to start accepting payments.',
				'woocommerce'
			) }
		</p>
		{ setupUrl && (
			<p className="woopayments-account-settings__actions">
				<Button href={ setupUrl } variant="primary">
					{ getSetupTitle() }
				</Button>
			</p>
		) }
	</section>
);

export const WooPaymentsAccountSettings = () => {
	const [ response, setResponse ] =
		useState< WooPaymentsAccountResponse | null >( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const [ reloadCount, setReloadCount ] = useState( 0 );
	const rootRef = useRef< HTMLDivElement | null >( null );
	const resultHeadingRef = useRef< HTMLHeadingElement | null >( null );
	const shouldFocusResultRef = useRef( false );

	useEffect( () => {
		let isMounted = true;

		setIsLoading( true );

		getWooPaymentsAccountSettings()
			.then( ( nextResponse ) => {
				if ( ! isMounted ) {
					return;
				}

				setResponse( nextResponse );
				setErrorMessage( null );
			} )
			.catch( ( error ) => {
				if ( ! isMounted ) {
					return;
				}

				setErrorMessage( getErrorMessage( error ) );
			} )
			.finally( () => {
				if ( isMounted ) {
					setIsLoading( false );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [ reloadCount ] );

	useEffect( () => {
		if (
			isLoading ||
			errorMessage ||
			! shouldFocusResultRef.current ||
			! resultHeadingRef.current
		) {
			return;
		}

		const ownerDocument = rootRef.current?.ownerDocument;
		const activeElement = ownerDocument?.activeElement;
		if (
			rootRef.current &&
			activeElement instanceof HTMLElement &&
			activeElement !== ownerDocument?.body &&
			! rootRef.current.contains( activeElement )
		) {
			shouldFocusResultRef.current = false;
			return;
		}

		resultHeadingRef.current.focus();
		shouldFocusResultRef.current = false;
	}, [ errorMessage, isLoading, response ] );

	const retryAccountLoad = () => {
		if ( isLoading ) {
			return;
		}

		shouldFocusResultRef.current = true;
		setReloadCount( ( count ) => count + 1 );
	};

	const setResultHeadingRef = ( node: HTMLHeadingElement | null ) => {
		resultHeadingRef.current = node;
	};

	const account = response?.account;
	const setupUrl = response?.urls?.setup;

	return (
		<div className="woopayments-account-settings" ref={ rootRef }>
			<header className="woopayments-account-settings__header">
				<h1>{ getSettingsTitle() }</h1>
			</header>

			{ isLoading && ! errorMessage && (
				<p
					className="woopayments-account-settings__loading"
					aria-live="polite"
				>
					{ getLoadingText() }
				</p>
			) }

			{ errorMessage && (
				<section
					className="woopayments-account-settings__section woopayments-account-settings__section--error"
					role="alert"
					aria-labelledby="woopayments-account-settings-error-heading"
				>
					<h2 id="woopayments-account-settings-error-heading">
						{ getErrorTitle() }
					</h2>
					<p>{ errorMessage }</p>
					<p className="woopayments-account-settings__actions">
						<Button
							variant="secondary"
							onClick={ retryAccountLoad }
							aria-disabled={ isLoading ? 'true' : undefined }
						>
							{ isLoading
								? __( 'Trying again…', 'woocommerce' )
								: __( 'Try again', 'woocommerce' ) }
						</Button>
					</p>
				</section>
			) }

			{ ! isLoading &&
				! errorMessage &&
				( account?.connected ? (
					<AccountDetails
						account={ account }
						headingRef={ setResultHeadingRef }
					/>
				) : (
					<SetupState
						setupUrl={ setupUrl }
						headingRef={ setResultHeadingRef }
					/>
				) ) }
		</div>
	);
};

export default WooPaymentsAccountSettings;

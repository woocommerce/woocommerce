/**
 * External dependencies
 */
import React, { useState, useEffect } from 'react';
import {
	loadConnectAndInitialize,
	LoadError,
	LoaderStart,
	StripeConnectInstance,
} from '@stripe/connect-js';
import {
	ConnectAccountOnboarding,
	ConnectComponentsProvider,
} from '@stripe/react-connect-js';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { createEmbeddedKycSession } from '../../utils/actions';
import appearance from './appearance';
import {
	type EmbeddedAccountInitializationFailure,
	EmbeddedKycSession,
	EmbeddedKycSessionCreateResult,
	OnboardingFields,
} from '../../types';
import BannerNotice from '../../../../components/banner-notice';
import { useOnboardingContext } from '../../../../data/onboarding-context';

interface EmbeddedComponentProps {
	onLoaderStart?: ( { elementTagName }: LoaderStart ) => void;
	onLoadError?: ( { error, elementTagName }: LoadError ) => void;
}

interface EmbeddedAccountOnboardingProps extends EmbeddedComponentProps {
	onboardingData: OnboardingFields;
	onExit: () => void;
	onStepChange?: ( step: string ) => void;
	onInitializationError?: (
		failure: EmbeddedAccountInitializationFailure
	) => void;
	collectPayoutRequirements?: boolean;
}

const defaultLocale = 'en-US';
const genericInitializationError = __(
	'Unable to start the business verification session. If this problem persists, please contact support.',
	'woocommerce'
);

const isObjectRecord = ( value: unknown ): value is Record< string, unknown > =>
	!! value && typeof value === 'object' && ! Array.isArray( value );

const getObjectKeys = ( value: unknown ): string[] =>
	isObjectRecord( value ) ? Object.keys( value ).sort() : [];

const isNonEmptyString = ( value: unknown ): value is string =>
	typeof value === 'string' && value.trim() !== '';

const normalizeLocale = ( value: unknown ): string =>
	isNonEmptyString( value )
		? value.trim().replace( /_/g, '-' )
		: defaultLocale;

const validateEmbeddedKycSessionCreateResult = (
	result: unknown
):
	| { result: EmbeddedKycSessionCreateResult }
	| { error: EmbeddedAccountInitializationFailure } => {
	const session = isObjectRecord( result ) ? result.session : undefined;
	const receivedKeys = isObjectRecord( session )
		? getObjectKeys( session )
		: getObjectKeys( result );

	if ( ! isObjectRecord( session ) ) {
		return {
			error: {
				reason: 'bad_session',
				message: genericInitializationError,
				receivedKeys,
			},
		};
	}

	if (
		! isNonEmptyString( session.clientSecret ) ||
		! isNonEmptyString( session.publishableKey )
	) {
		return {
			error: {
				reason: 'bad_session',
				message: genericInitializationError,
				receivedKeys,
			},
		};
	}

	const normalizedSession: EmbeddedKycSession = {
		...( session as unknown as EmbeddedKycSession ),
		clientSecret: session.clientSecret,
		publishableKey: session.publishableKey,
		locale: normalizeLocale( session.locale ),
	};

	return { result: { session: normalizedSession } };
};

/**
 * Hook to initialize Stripe Connect.
 *
 * @param onboardingData - Data required for onboarding.
 *
 * @return Returns stripeConnectInstance, error, and loading state.
 */
const useInitializeStripe = ( onboardingData: OnboardingFields ) => {
	const [ stripeConnectInstance, setStripeConnectInstance ] =
		useState< StripeConnectInstance | null >( null );
	const { currentStep, sessionEntryPoint: onboardingSource } =
		useOnboardingContext();
	const kycSessionUrl = currentStep?.actions?.kyc_session?.href ?? '';
	const [ initializationError, setInitializationError ] =
		useState< EmbeddedAccountInitializationFailure | null >( null );
	const [ loading, setLoading ] = useState< boolean >( true );

	useEffect( () => {
		const initializeStripe = async () => {
			try {
				const accountSession = await createEmbeddedKycSession(
					onboardingData,
					kycSessionUrl,
					onboardingSource
				);

				const validation =
					validateEmbeddedKycSessionCreateResult( accountSession );
				if ( 'error' in validation ) {
					setInitializationError( validation.error );
					return;
				}

				const { clientSecret, publishableKey, locale } =
					validation.result.session;

				const instance = loadConnectAndInitialize( {
					publishableKey,
					fetchClientSecret: async () => clientSecret,
					appearance: {
						overlays: 'drawer',
						...appearance,
					},
					locale,
				} );

				setStripeConnectInstance( instance );
			} catch ( err ) {
				setInitializationError( {
					reason: 'init_error',
					message: genericInitializationError,
				} );
			} finally {
				setLoading( false );
			}
		};

		void initializeStripe();
	}, [ kycSessionUrl, onboardingData, onboardingSource ] );

	return { stripeConnectInstance, initializationError, loading };
};

/* eslint-disable jsdoc/check-param-names */
/**
 * Embedded Stripe Account Onboarding Component.
 *
 * @param onboardingData                    - Data required for onboarding.
 * @param onExit                            - Callback function when the onboarding flow is exited.
 * @param onLoaderStart                     - Callback function when the onboarding loader starts.
 * @param onLoadError                       - Callback function when the onboarding load error occurs.
 * @param [onStepChange]                    - Callback function when the onboarding step changes.
 * @param [onInitializationError]           - Callback function when the onboarding initialization fails.
 * @param [collectPayoutRequirements=false] - Whether to collect payout requirements.
 *
 * @return Rendered Account Onboarding component.
 */
export const EmbeddedAccountOnboarding: React.FC<
	EmbeddedAccountOnboardingProps
> = ( {
	onboardingData,
	onExit,
	onLoaderStart,
	onLoadError,
	onStepChange,
	onInitializationError,
	collectPayoutRequirements = false,
} ) => {
	const { stripeConnectInstance, initializationError } =
		useInitializeStripe( onboardingData );

	useEffect( () => {
		if ( initializationError ) {
			onInitializationError?.( initializationError );
		}
	}, [ initializationError, onInitializationError ] );

	return (
		<>
			{ initializationError && ! onInitializationError && (
				<BannerNotice status="error">
					{ initializationError.message }
				</BannerNotice>
			) }
			{ stripeConnectInstance && (
				<ConnectComponentsProvider
					connectInstance={ stripeConnectInstance }
				>
					<ConnectAccountOnboarding
						onLoaderStart={ onLoaderStart }
						onLoadError={ onLoadError }
						onExit={ onExit }
						onStepChange={ ( stepChange ) =>
							onStepChange?.( stepChange.step )
						}
						collectionOptions={ {
							fields: collectPayoutRequirements
								? 'eventually_due'
								: 'currently_due',
							futureRequirements: 'omit',
						} }
					/>
				</ConnectComponentsProvider>
			) }
		</>
	);
};

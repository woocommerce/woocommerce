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
import { OnboardingFields } from '../../types';
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
	collectPayoutRequirements?: boolean;
}

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
	const { currentStep } = useOnboardingContext();
	const [ initializationError, setInitializationError ] = useState<
		string | null
	>( null );
	const [ loading, setLoading ] = useState< boolean >( true );

	useEffect( () => {
		const initializeStripe = async () => {
			try {
				const accountSession = await createEmbeddedKycSession(
					onboardingData,
					currentStep?.actions?.kyc_session?.href ?? ''
				);

				const { clientSecret, publishableKey } = accountSession.session;

				if ( ! publishableKey ) {
					throw new Error(
						__(
							'Unable to start onboarding. If this problem persists, please contact support.',
							'woocommerce'
						)
					);
				}

				const instance = loadConnectAndInitialize( {
					publishableKey,
					fetchClientSecret: async () => clientSecret,
					appearance: {
						overlays: 'drawer',
						...appearance,
					},
					locale: accountSession.session.locale.replace( '_', '-' ),
				} );

				setStripeConnectInstance( instance );
			} catch ( err ) {
				setInitializationError(
					err instanceof Error
						? err.message
						: __(
								'Unable to start onboarding. If this problem persists, please contact support.',
								'woocommerce'
						  )
				);
			} finally {
				setLoading( false );
			}
		};

		initializeStripe();
	}, [ onboardingData ] );

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
	collectPayoutRequirements = false,
} ) => {
	const { stripeConnectInstance, initializationError } =
		useInitializeStripe( onboardingData );

	return (
		<>
			{ initializationError && (
				<BannerNotice status="error">
					{ initializationError }
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

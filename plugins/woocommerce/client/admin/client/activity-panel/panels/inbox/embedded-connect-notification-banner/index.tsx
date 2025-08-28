/**
 * External dependencies
 */
import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { createSlotFill } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { ABBREVIATED_NOTIFICATION_SLOT_NAME } from '../abbreviated-notifications-panel';
import StripeSpinner from '../../../components/stripe-spinner';
import './style.scss';

interface EmbeddedAccountNotificationBannerProps {
	onLoaderStart?: ( loadStart: any ) => void;
	onLoadError?: ( error: any ) => void;
	onNotificationsChange?: ( notifications: {
		total: number;
		actionRequired: number;
	} ) => void;
	useSlotFill?: boolean; // Whether to use SlotFill system or render directly
}

interface AccountSession {
	clientSecret: string;
	publishableKey: string;
	locale: string;
}

/**
 * Create an account session for notifications.
 */
const createAccountSession = async (): Promise< AccountSession > => {
	const response = await apiFetch< { session: AccountSession } >( {
		path: '/wc-admin/settings/payments/woopayments/onboarding/step/business_verification/kyc_session',
		method: 'POST',
		data: {
			location: 'US',
			source: 'activity_panel_notifications',
			self_assessment: {},
		},
	} );
	return response.session;
};

const { Fill } = createSlotFill( ABBREVIATED_NOTIFICATION_SLOT_NAME );

/**
 * Embedded Stripe Notification Banner Component.
 *
 * @param onLoaderStart - Callback when Stripe component starts rendering.
 * @param onLoadError - Callback when Stripe component load error occurs.
 * @param onNotificationsChange - Callback triggered when notifications change.
 *
 * @return Rendered Notification Banner component.
 */
export const EmbeddedConnectNotificationBanner: React.FC< EmbeddedAccountNotificationBannerProps > = ( {
	onLoaderStart,
	onLoadError,
	onNotificationsChange,
	useSlotFill = false,
} ) => {
	const [ stripeConnectInstance, setStripeConnectInstance ] = useState< any >( null );
	const [ stripeComponents, setStripeComponents ] = useState< any >( null );
	const [ initializationError, setInitializationError ] = useState< string | null >( null );
	const [ loading, setLoading ] = useState( true );

	useEffect( () => {
		const initializeStripe = async () => {
			try {
				// Dynamic imports to avoid webpack issues
				const { loadConnectAndInitialize } = await import( '@stripe/connect-js' );
				const stripeReactComponents = await import( '@stripe/react-connect-js' );
				
				const session = await createAccountSession();

				if ( ! session.publishableKey ) {
					throw new Error(
						__( 'Unable to initialize WooCommerce Payments notifications.', 'woocommerce' )
					);
				}

				const instance = loadConnectAndInitialize( {
					publishableKey: session.publishableKey,
					fetchClientSecret: async () => session.clientSecret,
					appearance: {
						overlays: 'drawer',
						variables: {
							colorPrimary: '#873EFF',
							colorBackground: '#FFFFFF',
							fontFamily: "-apple-system, BlinkMacSystemFont, 'system-ui', 'Segoe UI', sans-serif",
						},
					},
					locale: session.locale.replace( '_', '-' ),
				} );

				setStripeConnectInstance( instance );
				setStripeComponents( stripeReactComponents );
			} catch ( err ) {
				setInitializationError(
					err instanceof Error
						? err.message
						: __( 'Failed to initialize WooCommerce Payments notifications.', 'woocommerce' )
				);
			} finally {
				setLoading( false );
			}
		};

		initializeStripe();
	}, [] );

	// Follow exact woocommerce-payments pattern
	const content = (
		<>
			{ ( loading || ! stripeConnectInstance ) && <StripeSpinner /> }
			{ initializationError && (
				<div className="woocommerce-embedded-connect-notification-banner error">
					{ initializationError }
				</div>
			) }
			{ stripeConnectInstance && stripeComponents && (
				<stripeComponents.ConnectComponentsProvider connectInstance={ stripeConnectInstance }>
					<stripeComponents.ConnectNotificationBanner
						onLoaderStart={ onLoaderStart }
						onLoadError={ onLoadError }
						onNotificationsChange={ onNotificationsChange }
						collectionOptions={ {
							fields: 'eventually_due',
							futureRequirements: 'omit',
						} }
					/>
				</stripeComponents.ConnectComponentsProvider>
			) }
		</>
	);

	// Use SlotFill if requested, otherwise render directly
	return useSlotFill ? <Fill>{ content }</Fill> : content;
};
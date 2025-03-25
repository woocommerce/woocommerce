/**
 * External dependencies
 */
import { useEffect } from 'react';
import { Button, Card, CardBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Link } from '@woocommerce/components';
import { PaymentIncentive, PaymentProvider } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';
import './incentive-banner.scss';
import { StatusBadge } from '~/settings-payments/components/status-badge';
import { isIncentiveDismissedInContext } from '~/settings-payments/utils';

interface IncentiveBannerProps {
	/**
	 * Incentive data.
	 */
	incentive: PaymentIncentive;
	/**
	 * Payment provider.
	 */
	provider: PaymentProvider;
	/**
	 * Onboarding URL (if available).
	 */
	onboardingUrl: string | null;
	/**
	 * Callback used when an incentive is accepted.
	 *
	 * @param id Incentive ID.
	 */
	onAccept: ( id: string ) => void;
	/**
	 * Callback to handle dismiss action.
	 *
	 * @param dismissUrl Dismiss URL.
	 * @param context    The context in which the incentive is dismissed. (e.g. whether it was in a modal or banner).
	 */
	onDismiss: ( dismissUrl: string, context: string ) => void;
	/**
	 * Callback to setup the plugin.
	 *
	 * @param id            Extension ID.
	 * @param slug          Extension slug.
	 * @param onboardingUrl Onboarding URL (if available).
	 */
	setupPlugin: (
		id: string,
		slug: string,
		onboardingUrl: string | null
	) => void;
}

/**
 * A banner component that displays a promotional incentive to the user. The banner allows the user to:
 * - Accept the incentive, triggering setup actions.
 * - Dismiss the incentive, removing it from the current context.
 */
export const IncentiveBanner = ( {
	incentive,
	provider,
	onboardingUrl,
	onDismiss,
	onAccept,
	setupPlugin,
}: IncentiveBannerProps ) => {
	const [ isSubmitted, setIsSubmitted ] = useState( false );
	const [ isDismissed, setIsDismissed ] = useState( false );
	const [ isBusy, setIsBusy ] = useState( false );

	const context = 'wc_settings_payments__banner';

	useEffect( () => {
		// Record the event when the incentive is shown.
		recordEvent( 'settings_payments_incentive_show', {
			incentive_id: incentive.promo_id,
			provider_id: provider.id,
			display_context: context,
		} );
	}, [ incentive.promo_id, provider.id ] );

	/**
	 * Handles accepting the incentive.
	 * Triggers the onAccept callback, dismisses the banner, and triggers plugin setup.
	 */
	const handleAccept = () => {
		// Record the event when the user accepts the incentive.
		recordEvent( 'settings_payments_incentive_accept', {
			incentive_id: incentive.promo_id,
			provider_id: provider.id,
			display_context: context,
		} );

		// Accept the incentive and setup the plugin.
		setIsBusy( true );
		onAccept( incentive.promo_id );
		onDismiss( incentive._links.dismiss.href, context ); // We also dismiss the incentive when it is accepted.
		setIsSubmitted( true );
		setupPlugin( provider.id, provider.plugin.slug, onboardingUrl );
		setIsBusy( false );
	};

	/**
	 * Handles dismissing the incentive.
	 * Triggers the onDismiss callback and hides the banner.
	 */
	const handleDismiss = () => {
		// Record the event when the user dismisses the incentive.
		recordEvent( 'settings_payments_incentive_dismiss', {
			incentive_id: incentive.promo_id,
			provider_id: provider.id,
			display_context: context,
		} );

		// Dimiss the incentive.
		setIsBusy( true );
		onDismiss( incentive._links.dismiss.href, context );
		setIsBusy( false );
		setIsDismissed( true );
	};

	// Do not render the banner if it has been submitted, dismissed, or already dismissed in this context.
	if (
		isSubmitted ||
		isIncentiveDismissedInContext( incentive, context ) ||
		isDismissed
	) {
		return null;
	}

	return (
		<Card className="woocommerce-incentive-banner" isRounded={ true }>
			<div className="woocommerce-incentive-banner__content">
				<div className={ 'woocommerce-incentive-banner__image' }>
					<img
						src={
							WC_ASSET_URL +
							'images/settings-payments/incentives-illustration.svg'
						}
						alt={ __( 'Incentive illustration', 'woocommerce' ) }
					/>
				</div>
				<CardBody className="woocommerce-incentive-banner__body">
					<StatusBadge
						status="has_incentive"
						message={ __( 'Limited time offer', 'woocommerce' ) }
					/>

					<div className={ 'woocommerce-incentive-banner__copy' }>
						<h2>{ incentive.title }</h2>
						<p>{ incentive.description }</p>
					</div>

					<div className={ 'woocommerce-incentive-banner__terms' }>
						{ createInterpolateElement(
							__(
								'See <termsLink /> for details.',
								'woocommerce'
							),
							{
								termsLink: (
									<Link
										href={ incentive.tc_url }
										target="_blank"
										rel="noreferrer"
										type="external"
									>
										{ __(
											'Terms and Conditions',
											'woocommerce'
										) }
									</Link>
								),
							}
						) }
					</div>

					<div className={ 'woocommerce-incentive-banner__actions' }>
						<Button
							variant={ 'primary' }
							isBusy={ isSubmitted }
							disabled={ isSubmitted }
							onClick={ handleAccept }
						>
							{ incentive.cta_label }
						</Button>
						<Button
							variant={ 'tertiary' }
							isBusy={ isBusy }
							disabled={ isBusy }
							onClick={ handleDismiss }
						>
							{ __( 'Dismiss', 'woocommerce' ) }
						</Button>
					</div>
				</CardBody>
			</div>
		</Card>
	);
};

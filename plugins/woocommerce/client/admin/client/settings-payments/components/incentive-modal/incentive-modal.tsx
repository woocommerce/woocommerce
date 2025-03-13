/**
 * External dependencies
 */
import { useEffect } from 'react';
import {
	Button,
	Card,
	CardBody,
	CardMedia,
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Link } from '@woocommerce/components';
import { PaymentIncentive, PaymentProvider } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './incentive-modal.scss';
import { StatusBadge } from '~/settings-payments/components/status-badge';
import { WC_ASSET_URL } from '~/utils/admin-settings';
import { isIncentiveDismissedInContext } from '~/settings-payments/utils';

interface IncentiveModalProps {
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
	 * @param dismissHref Dismiss URL.
	 * @param context     The context in which the incentive is dismissed. (e.g. whether it was in a modal or banner).
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
 * A modal component that displays a promotional incentive to the user.
 * The modal allows the user to:
 * - Accept the incentive, triggering setup actions.
 * - Dismiss the incentive, removing it from the current context.
 *
 * This component manages its own visibility state. If the incentive is already dismissed
 * for the current context, the modal does not render.
 */
export const IncentiveModal = ( {
	incentive,
	provider,
	onboardingUrl,
	onAccept,
	onDismiss,
	setupPlugin,
}: IncentiveModalProps ) => {
	const [ isBusy, setIsBusy ] = useState( false );
	const [ isOpen, setIsOpen ] = useState( true );

	const context = 'wc_settings_payments__modal';
	const isDismissed = isIncentiveDismissedInContext( incentive, context );

	useEffect( () => {
		// Record the event when the incentive is shown.
		recordEvent( 'settings_payments_incentive_show', {
			incentive_id: incentive.promo_id,
			provider_id: provider.id,
			display_context: context,
		} );
	}, [ incentive.promo_id, provider.id ] );

	/**
	 * Closes the modal.
	 */
	const handleClose = () => {
		setIsOpen( false );
	};

	/**
	 * Handles accepting the incentive.
	 * Triggers the onAccept callback, dismisses the incentive, closes the modal, and trigger plugin setup.
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
		handleClose(); // Close the modal.
		setupPlugin( provider.id, provider.plugin.slug, onboardingUrl );
		setIsBusy( false );
	};

	/**
	 * Handles dismissing the incentive.
	 * Triggers the onDismiss callback and hides the modal.
	 */
	const handleDismiss = () => {
		// Record the event when the user dismisses the incentive.
		recordEvent( 'settings_payments_incentive_dismiss', {
			incentive_id: incentive.promo_id,
			provider_id: provider.id,
			display_context: context,
		} );

		// Dimiss the incentive.
		onDismiss( incentive._links.dismiss.href, context );
		handleClose();
	};

	// Do not render the modal if the incentive is dismissed in this context.
	if ( isDismissed ) {
		return null;
	}

	return (
		<>
			{ isOpen && (
				<Modal
					title=""
					className="woocommerce-incentive-modal"
					onRequestClose={ handleDismiss }
				>
					<Card className={ 'woocommerce-incentive-modal__card' }>
						<div className="woocommerce-incentive-modal__content">
							<CardMedia
								className={
									'woocommerce-incentive-modal__media'
								}
							>
								<img
									src={
										WC_ASSET_URL +
										'images/settings-payments/incentives-illustration.svg'
									}
									alt={ __(
										'Incentive illustration',
										'woocommerce'
									) }
								/>
							</CardMedia>
							<CardBody
								className={
									'woocommerce-incentive-modal__body'
								}
							>
								<div>
									<StatusBadge
										status={ 'has_incentive' }
										message={ __(
											'Limited time offer',
											'woocommerce'
										) }
									/>
								</div>
								<h2>{ incentive.title }</h2>
								<p>{ incentive.description }</p>
								<p
									className={
										'woocommerce-incentive-modal__terms'
									}
								>
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
								</p>
								<div className="woocommerce-incentive-model__actions">
									<Button
										variant={ 'primary' }
										isBusy={ isBusy }
										disabled={ isBusy }
										onClick={ handleAccept }
									>
										{ incentive.cta_label }
									</Button>
								</div>
							</CardBody>
						</div>
					</Card>
				</Modal>
			) }
		</>
	);
};

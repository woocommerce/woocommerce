/**
 * External dependencies
 */
import { WooPaymentsMethodsLogos } from '@woocommerce/onboarding';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { PaymentGatewayProvider } from '@woocommerce/data';
import { Tooltip } from '@wordpress/components';

/**
 * Internal dependencies
 */
import sanitizeHTML from '~/lib/sanitize-html';
import { StatusBadge } from '~/settings-payments/components/status-badge';
import { EllipsisMenuWrapper as EllipsisMenu } from '~/settings-payments/components/ellipsis-menu-content';
import {
	hasIncentive,
	isWooPayEligible,
	isWooPayments,
} from '~/settings-payments/utils';
import { DefaultDragHandle } from '~/settings-payments/components/sortable';
import { WC_ASSET_URL } from '~/utils/admin-settings';
import {
	ActivatePaymentsButton,
	CompleteSetupButton,
	EnableGatewayButton,
	SettingsButton,
} from '~/settings-payments/components/buttons';
import { ReactivateLivePaymentsButton } from '~/settings-payments/components/buttons/reactivate-live-payments-button';
import { IncentiveStatusBadge } from '~/settings-payments/components/incentive-status-badge';
import { OfficialBadge } from '~/settings-payments/components/official-badge';

type PaymentGatewayItemProps = {
	gateway: PaymentGatewayProvider;
	installingPlugin: string | null;
	acceptIncentive: ( id: string ) => void;
};

export const PaymentGatewayListItem = ( {
	gateway,
	installingPlugin,
	acceptIncentive,
	...props
}: PaymentGatewayItemProps ) => {
	const itemIsWooPayments = isWooPayments( gateway.id );
	const incentive = hasIncentive( gateway ) ? gateway._incentive : null;
	const shouldHighlightIncentive =
		incentive && ! incentive?.promo_id.includes( '-action-' );

	const gatewayHasRecommendedPaymentMethods =
		( gateway.onboarding.recommended_payment_methods ?? [] ).length > 0;

	// If the account is not connected or the onboarding is not started, or not completed then the gateway needs onboarding.
	const gatewayNeedsOnboarding =
		! gateway.state.account_connected ||
		( gateway.state.account_connected &&
			! gateway.onboarding.state.started ) ||
		( gateway.state.account_connected &&
			gateway.onboarding.state.started &&
			! gateway.onboarding.state.completed );

	const determineGatewayStatus = () => {
		if ( ! gateway.state.enabled && gateway.state.needs_setup ) {
			return 'needs_setup';
		}
		if ( gateway.state.enabled ) {
			// A test account also implies test mode.
			if ( gateway.onboarding.state.test_mode ) {
				return 'test_account';
			}

			if ( gateway.state.test_mode ) {
				return 'test_mode';
			}

			return 'active';
		}

		return 'inactive';
	};

	return (
		<div
			id={ gateway.id }
			className={ `transitions-disabled woocommerce-list__item woocommerce-list__item-enter-done woocommerce-item__payment-gateway ${
				itemIsWooPayments
					? `woocommerce-item__woocommerce-payments`
					: ''
			} ${ shouldHighlightIncentive ? `has-incentive` : '' }` }
			{ ...props }
		>
			<div className="woocommerce-list__item-inner">
				<div className="woocommerce-list__item-before">
					<DefaultDragHandle />
					{ gateway.icon && (
						<img
							className={ 'woocommerce-list__item-image' }
							src={ gateway.icon }
							alt={ gateway.title + ' logo' }
						/>
					) }
				</div>
				<div className="woocommerce-list__item-text">
					<span className="woocommerce-list__item-title">
						{ gateway.title }
						{ incentive ? (
							<IncentiveStatusBadge incentive={ incentive } />
						) : (
							<StatusBadge status={ determineGatewayStatus() } />
						) }
						{ gateway.supports?.includes( 'subscriptions' ) && (
							<Tooltip
								placement="top"
								text={ __(
									'Supports recurring payments',
									'woocommerce'
								) }
								children={
									<img
										className="woocommerce-list__item-recurring-payments-icon"
										src={
											WC_ASSET_URL +
											'images/icons/recurring-payments.svg'
										}
										alt={ __(
											'Icon to indicate support for recurring payments',
											'woocommerce'
										) }
									/>
								}
							/>
						) }
						{ /* If the gateway has a matching suggestion, it is an official extension. */ }
						{ gateway._suggestion_id && (
							<OfficialBadge variant="expanded" />
						) }
					</span>
					<span
						className="woocommerce-list__item-content"
						// eslint-disable-next-line react/no-danger -- This string is sanitized by the PaymentGateway class.
						dangerouslySetInnerHTML={ sanitizeHTML(
							decodeEntities( gateway.description )
						) }
					/>
					{ itemIsWooPayments && (
						<WooPaymentsMethodsLogos
							maxElements={ 10 }
							tabletWidthBreakpoint={ 1080 } // Reduce the number of logos earlier.
							mobileWidthBreakpoint={ 768 } // Reduce the number of logos earlier.
							isWooPayEligible={ isWooPayEligible( gateway ) }
						/>
					) }
				</div>
				<div className="woocommerce-list__item-buttons">
					<div className="woocommerce-list__item-buttons__actions">
						{ ! gateway.state.enabled &&
							! gatewayNeedsOnboarding && (
								<EnableGatewayButton
									gatewayId={ gateway.id }
									gatewayState={ gateway.state }
									settingsHref={
										gateway.management._links.settings.href
									}
									onboardingHref={
										gateway.onboarding._links.onboard.href
									}
									isOffline={ false }
									gatewayHasRecommendedPaymentMethods={
										gatewayHasRecommendedPaymentMethods
									}
									installingPlugin={ installingPlugin }
									incentive={ incentive }
									acceptIncentive={ acceptIncentive }
								/>
							) }

						{ ! gatewayNeedsOnboarding && (
							<SettingsButton
								gatewayId={ gateway.id }
								settingsHref={
									gateway.management._links.settings.href
								}
								isInstallingPlugin={ !! installingPlugin }
							/>
						) }

						{ gatewayNeedsOnboarding && (
							<CompleteSetupButton
								gatewayId={ gateway.id }
								gatewayState={ gateway.state }
								onboardingState={ gateway.onboarding.state }
								settingsHref={
									gateway.management._links.settings.href
								}
								onboardingHref={
									gateway.onboarding._links.onboard.href
								}
								gatewayHasRecommendedPaymentMethods={
									gatewayHasRecommendedPaymentMethods
								}
								installingPlugin={ installingPlugin }
							/>
						) }

						{ isWooPayments( gateway.id ) &&
							// There is no actual switch-to-live in dev mode.
							! gateway.state.dev_mode &&
							gateway.state.account_connected &&
							gateway.onboarding.state.completed &&
							gateway.onboarding.state.test_mode && (
								<ActivatePaymentsButton
									acceptIncentive={ acceptIncentive }
									installingPlugin={ installingPlugin }
									incentive={ incentive }
								/>
							) }

						{ isWooPayments( gateway.id ) &&
							// There are no live payments in dev mode or test accounts, so no point in reactivating them.
							! gateway.state.dev_mode &&
							gateway.state.account_connected &&
							gateway.onboarding.state.completed &&
							! gateway.onboarding.state.test_mode &&
							gateway.state.test_mode && (
								<ReactivateLivePaymentsButton
									settingsHref={
										gateway.management._links.settings.href
									}
								/>
							) }
					</div>
				</div>
				<div className="woocommerce-list__item-after">
					<div className="woocommerce-list__item-after__actions">
						<EllipsisMenu
							label={ __(
								'Payment Provider Options',
								'woocommerce'
							) }
							provider={ gateway }
						/>
					</div>
				</div>
			</div>
		</div>
	);
};

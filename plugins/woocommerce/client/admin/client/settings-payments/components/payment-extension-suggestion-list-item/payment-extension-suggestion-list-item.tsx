/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { WooPaymentsMethodsLogos } from '@woocommerce/onboarding';
import {
	PaymentsExtensionSuggestionProvider,
	PaymentsEntity,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import sanitizeHTML from '~/lib/sanitize-html';
import { EllipsisMenuWrapper as EllipsisMenu } from '~/settings-payments/components/ellipsis-menu-content';
import {
	isWooPayments,
	hasIncentive,
	isWooPayEligible,
	recordPaymentsEvent,
} from '~/settings-payments/utils';
import { DefaultDragHandle } from '~/settings-payments/components/sortable';
import { StatusBadge } from '~/settings-payments/components/status-badge';
import { IncentiveStatusBadge } from '~/settings-payments/components/incentive-status-badge';
import { OfficialBadge } from '~/settings-payments/components/official-badge';

type PaymentExtensionSuggestionListItemProps = {
	/**
	 * The payment extension suggestion to display.
	 */
	suggestion: PaymentsExtensionSuggestionProvider;
	/**
	 * The ID of the plugin currently being installed, or `null` if none.
	 */
	installingPlugin: string | null;
	/**
	 * Callback to set up the plugin.
	 *
	 * @param provider      Extension provider.
	 * @param onboardingUrl Extension onboarding URL (if available).
	 * @param attachUrl     Extension attach URL (if available).
	 */
	setUpPlugin: (
		provider: PaymentsEntity,
		onboardingUrl: string | null,
		attachUrl: string | null
	) => void;
	/**
	 * Indicates whether the plugin is already installed.
	 */
	pluginInstalled: boolean;
	/**
	 * Callback function to handle accepting an incentive. Receives the incentive ID as a parameter.
	 */
	acceptIncentive: ( id: string ) => void;
	/**
	 * Indicates whether the incentive should be highlighted.
	 */
	shouldHighlightIncentive?: boolean;
};

/**
 * A component that renders an individual payment extension suggestion in a list.
 * Displays extension details including title, description, and an action button
 * for installation or enabling the plugin. The component highlights incentive if available.
 */
export const PaymentExtensionSuggestionListItem = ( {
	suggestion,
	installingPlugin,
	setUpPlugin,
	pluginInstalled,
	acceptIncentive,
	shouldHighlightIncentive = false,
	...props
}: PaymentExtensionSuggestionListItemProps ) => {
	const incentive = hasIncentive( suggestion ) ? suggestion._incentive : null;

	// Determine the CTA button label based on the extension state.
	let ctaButtonLabel = __( 'Install', 'woocommerce' );
	if ( pluginInstalled ) {
		ctaButtonLabel = __( 'Enable', 'woocommerce' );
	} else if ( installingPlugin === suggestion.id ) {
		ctaButtonLabel = __( 'Installing', 'woocommerce' );
	}

	return (
		<div
			id={ suggestion.id }
			className={ `transitions-disabled woocommerce-list__item woocommerce-list__item-enter-done ${
				hasIncentive( suggestion ) && shouldHighlightIncentive
					? `has-incentive`
					: ''
			}` }
			{ ...props }
		>
			<div className="woocommerce-list__item-inner">
				<div className="woocommerce-list__item-before">
					<DefaultDragHandle />
					{ suggestion.icon && (
						<img
							className={ 'woocommerce-list__item-image' }
							src={ suggestion.icon }
							alt={ suggestion.title + ' logo' }
						/>
					) }
				</div>
				<div className="woocommerce-list__item-text">
					<span className="woocommerce-list__item-title">
						{ suggestion.title }{ ' ' }
						{ ! hasIncentive( suggestion ) &&
							isWooPayments( suggestion.id ) && (
								<StatusBadge status="recommended" />
							) }
						{ incentive && (
							<IncentiveStatusBadge incentive={ incentive } />
						) }
						{ /* All payment extension suggestions are official. */ }
						<OfficialBadge
							variant="expanded"
							suggestionId={ suggestion.id }
						/>
					</span>
					<span
						className="woocommerce-list__item-content"
						dangerouslySetInnerHTML={ sanitizeHTML(
							decodeEntities( suggestion.description )
						) }
					/>
					{ isWooPayments( suggestion.id ) && (
						<WooPaymentsMethodsLogos
							maxElements={ 10 }
							tabletWidthBreakpoint={ 1080 } // Reduce the number of logos earlier.
							mobileWidthBreakpoint={ 768 } // Reduce the number of logos earlier.
							isWooPayEligible={ isWooPayEligible( suggestion ) }
						/>
					) }
				</div>
				<div className="woocommerce-list__item-buttons">
					<div className="woocommerce-list__item-buttons__actions">
						<Button
							variant="primary"
							onClick={ () => {
								if ( pluginInstalled ) {
									// Record the event when user clicks on a gateway's enable button.
									recordPaymentsEvent(
										'provider_enable_click',
										{
											provider_id: suggestion.id,
											suggestion_id: suggestion.id,
										}
									);
								}

								if ( incentive ) {
									acceptIncentive( incentive.promo_id );
								}

								setUpPlugin(
									suggestion,
									suggestion.onboarding?._links?.onboard
										?.href ?? null,
									pluginInstalled
										? null
										: suggestion._links?.attach?.href ??
												null
								);
							} }
							isBusy={ installingPlugin === suggestion.id }
							disabled={ !! installingPlugin }
						>
							{ ctaButtonLabel }
						</Button>
					</div>
				</div>
				<div className="woocommerce-list__item-after">
					<div className="woocommerce-list__item-after__actions">
						<EllipsisMenu
							label={ __(
								'Payment provider actions',
								'woocommerce'
							) }
							provider={ suggestion }
						/>
					</div>
				</div>
			</div>
		</div>
	);
};

/**
 * External dependencies
 */
import {
	PaymentsEntity,
	PaymentsProvider,
	PaymentsProviderType,
	PaymentGatewayProvider,
	OfflinePmsGroupProvider,
	PaymentsExtensionSuggestionProvider,
} from '@woocommerce/data';
import { Gridicon } from '@automattic/components';
import { useNavigate } from 'react-router-dom';
import { isRTL } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	DefaultDragHandle,
	SortableContainer,
	SortableItem,
} from '~/settings-payments/components/sortable';
import { PaymentExtensionSuggestionListItem } from '~/settings-payments/components/payment-extension-suggestion-list-item';
import { PaymentGatewayListItem } from '~/settings-payments/components/payment-gateway-list-item';
import './payment-gateway-list.scss';
import { removeOriginFromURL } from '~/settings-payments/utils';

interface PaymentGatewayListProps {
	/**
	 * List of payments providers to display.
	 */
	providers: PaymentsProvider[];
	/**
	 * Array of slugs for installed plugins.
	 */
	installedPluginSlugs: string[];
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
	 * @param context       The context from which the plugin is set up (e.g. 'wc_settings_payments__main_suggestion').
	 */
	setUpPlugin: (
		provider: PaymentsEntity,
		onboardingUrl: string | null,
		attachUrl: string | null,
		context?: string
	) => void;
	/**
	 * Callback to handle accepting an incentive. Receives the incentive ID as a parameter.
	 */
	acceptIncentive: ( id: string ) => void;
	/**
	 * Indicates whether the incentive should be highlighted.
	 */
	shouldHighlightIncentive: boolean;
	/**
	 * Callback to update the ordering of payments providers after sorting.
	 */
	updateOrdering: ( providers: PaymentsProvider[] ) => void;
	/**
	 * Callback to open or close the onboarding modal.
	 */
	setIsOnboardingModalOpen: ( isOpen: boolean ) => void;
}

/**
 * A component that renders a sortable list of payment providers. Depending on the provider type, it displays
 * different components such as `PaymentExtensionSuggestionListItem`, `PaymentGatewayListItem`, or a custom
 * clickable item for offline payment groups.
 *
 * The list supports drag-and-drop reordering and dynamic actions like installing plugins, enabling gateways,
 * and handling incentives.
 */
export const PaymentGatewayList = ( {
	providers,
	installedPluginSlugs,
	installingPlugin,
	setUpPlugin,
	acceptIncentive,
	shouldHighlightIncentive,
	updateOrdering,
	setIsOnboardingModalOpen,
}: PaymentGatewayListProps ) => {
	const navigate = useNavigate();

	return (
		<SortableContainer< PaymentsProvider >
			items={ providers }
			className={ 'settings-payment-gateways__list' }
			setItems={ updateOrdering }
		>
			{ providers.map( ( provider: PaymentsProvider ) => {
				switch ( provider._type ) {
					// Return different components wrapped into SortableItem depending on the provider type.
					case PaymentsProviderType.Suggestion:
						const suggestion =
							provider as PaymentsExtensionSuggestionProvider;
						const pluginInstalled = installedPluginSlugs.includes(
							provider.plugin.slug
						);
						return (
							<SortableItem
								key={ suggestion.id }
								id={ suggestion.id }
							>
								{ PaymentExtensionSuggestionListItem( {
									suggestion,
									installingPlugin,
									setUpPlugin,
									pluginInstalled,
									acceptIncentive,
									shouldHighlightIncentive,
								} ) }
							</SortableItem>
						);
					case PaymentsProviderType.Gateway:
						const gateway = provider as PaymentGatewayProvider;
						return (
							<SortableItem
								key={ provider.id }
								id={ provider.id }
							>
								{ PaymentGatewayListItem( {
									gateway,
									installingPlugin,
									acceptIncentive,
									shouldHighlightIncentive,
									setIsOnboardingModalOpen,
								} ) }
							</SortableItem>
						);
					case PaymentsProviderType.OfflinePmsGroup:
						// Offline payments item logic is described below.
						const offlinePmsGroup =
							provider as OfflinePmsGroupProvider;
						return (
							<SortableItem
								key={ offlinePmsGroup.id }
								id={ offlinePmsGroup.id }
							>
								{ /* eslint-disable-next-line jsx-a11y/click-events-have-key-events,jsx-a11y/no-static-element-interactions */ }
								<div
									id={ offlinePmsGroup.id }
									className="transitions-disabled woocommerce-list__item clickable-list-item enter-done"
									onClick={ () => {
										navigate(
											removeOriginFromURL(
												offlinePmsGroup.management
													._links.settings.href
											)
										);
									} }
								>
									<div className="woocommerce-list__item-inner">
										<div className="woocommerce-list__item-before">
											<DefaultDragHandle />
											<img
												src={ offlinePmsGroup.icon }
												alt={
													offlinePmsGroup.title +
													' logo'
												}
											/>
										</div>
										<div className="woocommerce-list__item-text">
											<span className="woocommerce-list__item-title">
												{ offlinePmsGroup.title }
											</span>
											<span
												className="woocommerce-list__item-content"
												// eslint-disable-next-line react/no-danger -- This string is sanitized by the PaymentGateway class.
												dangerouslySetInnerHTML={ {
													__html: offlinePmsGroup.description,
												} }
											/>
										</div>
										<div className="woocommerce-list__item-after centered no-buttons">
											<div className="woocommerce-list__item-after__actions">
												<a
													className="woocommerce-list__item-after__actions__arrow"
													href={
														offlinePmsGroup
															.management._links
															.settings.href
													}
													aria-label={
														offlinePmsGroup.title
													}
												>
													<Gridicon
														icon={
															isRTL()
																? 'chevron-left'
																: 'chevron-right'
														}
													/>
												</a>
											</div>
										</div>
									</div>
								</div>
							</SortableItem>
						);
					default:
						return null;
				}
			} ) }
		</SortableContainer>
	);
};

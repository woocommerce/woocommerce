/**
 * External dependencies
 */
import {
	PaymentProvider,
	PaymentProviderType,
	PaymentGatewayProvider,
	OfflinePmsGroupProvider,
	PaymentExtensionSuggestionProvider,
} from '@woocommerce/data';
import { Gridicon } from '@automattic/components';

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

interface PaymentGatewayListProps {
	/**
	 * List of payment providers to display.
	 */
	providers: PaymentProvider[];
	/**
	 * Array of slugs for installed plugins.
	 */
	installedPluginSlugs: string[];
	/**
	 * The ID of the plugin currently being installed, or `null` if none.
	 */
	installingPlugin: string | null;
	/**
	 * Callback to handle the setup of a plugin. Receives the plugin ID, slug, and onboarding URL (if available).
	 */
	setupPlugin: (
		id: string,
		slug: string,
		onboardingUrl: string | null
	) => void;
	/**
	 * Callback to handle accepting an incentive. Receives the incentive ID as a parameter.
	 */
	acceptIncentive: ( id: string ) => void;
	/**
	 * Callback to update the ordering of payment providers after sorting.
	 */
	updateOrdering: ( providers: PaymentProvider[] ) => void;
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
	setupPlugin,
	acceptIncentive,
	updateOrdering,
}: PaymentGatewayListProps ) => {
	return (
		<SortableContainer< PaymentProvider >
			items={ providers }
			className={ 'settings-payment-gateways__list' }
			setItems={ updateOrdering }
		>
			{ providers.map( ( provider: PaymentProvider ) => {
				switch ( provider._type ) {
					// Return different components wrapped into SortableItem depending on the provider type.
					case PaymentProviderType.Suggestion:
						const suggestion =
							provider as PaymentExtensionSuggestionProvider;
						const pluginInstalled = installedPluginSlugs.includes(
							provider.plugin.slug
						);
						return (
							<SortableItem
								key={ suggestion.id }
								id={ suggestion.id }
							>
								{ PaymentExtensionSuggestionListItem( {
									extension: suggestion,
									installingPlugin,
									setupPlugin,
									pluginInstalled,
									acceptIncentive,
								} ) }
							</SortableItem>
						);
					case PaymentProviderType.Gateway:
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
								} ) }
							</SortableItem>
						);
					case PaymentProviderType.OfflinePmsGroup:
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
										window.location.href =
											offlinePmsGroup.management._links.settings.href;
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
												>
													<Gridicon icon="chevron-right" />
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

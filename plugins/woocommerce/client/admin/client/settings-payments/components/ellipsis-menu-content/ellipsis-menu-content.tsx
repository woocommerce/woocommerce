/**
 * External dependencies
 */
import { Button, CardDivider } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	pluginsStore,
	paymentSettingsStore,
	PaymentsProviderLink,
	PaymentsProvider,
} from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './ellipsis-menu-content.scss';
import { recordPaymentsProviderEvent } from '~/settings-payments/utils';

interface EllipsisMenuContentProps {
	/**
	 * The provider details.
	 */
	provider: PaymentsProvider;
	/**
	 * The main plugin file path of the plugin associated with the payment gateway.
	 */
	pluginFile: string;
	/**
	 * Indicates if the menu is being used for a payment extension suggestion.
	 */
	isSuggestion: boolean;
	/**
	 * Callback to close the ellipsis menu.
	 */
	onToggle: () => void;
	/**
	 * Array of links related to the payment provider.
	 */
	links?: PaymentsProviderLink[];
	/**
	 * Indicates if the account can be reset. Optional.
	 */
	canResetAccount?: boolean;
	/**
	 * Callback to show or hide the reset account modal. Optional.
	 */
	setResetAccountModalVisible?: ( isVisible: boolean ) => void;
	/**
	 * Indicates if the payment gateway is enabled for payment processing. Optional.
	 */
	isEnabled?: boolean;
	/**
	 * Indicates if the onboarding can be reset. Optional.
	 */
	canResetOnboarding?: boolean;
}

/**
 * A component for rendering the content of an ellipsis menu in the WooCommerce payment settings.
 * The menu provides provider links and options to manage payment providers, such as enabling, disabling, deactivating gateways,
 * hiding suggestions, and resetting accounts.
 */
export const EllipsisMenuContent = ( {
	provider,
	pluginFile,
	isSuggestion,
	onToggle,
	links = [],
	canResetAccount = false,
	setResetAccountModalVisible = () => {},
	isEnabled = false,
	canResetOnboarding = false,
}: EllipsisMenuContentProps ) => {
	const { deactivatePlugin } = useDispatch( pluginsStore );
	const [ isDeactivating, setIsDeactivating ] = useState( false );
	const [ isDisabling, setIsDisabling ] = useState( false );
	const [ isHidingSuggestion, setIsHidingSuggestion ] = useState( false );

	const {
		invalidateResolutionForStoreSelector,
		togglePaymentGateway,
		hidePaymentExtensionSuggestion,
	} = useDispatch( paymentSettingsStore );
	const { createErrorNotice, createSuccessNotice } =
		useDispatch( 'core/notices' );

	const typeToDisplayName: { [ key: string ]: string } = {
		pricing: __( 'See pricing & fees', 'woocommerce' ),
		about: __( 'Learn more', 'woocommerce' ),
		terms: __( 'See Terms of Service', 'woocommerce' ),
		support: __( 'Get support', 'woocommerce' ),
		documentation: __( 'View documentation', 'woocommerce' ),
	};

	/**
	 * Deactivates the provider extension.
	 */
	const deactivateProviderExtension = () => {
		setIsDeactivating( true );
		deactivatePlugin( pluginFile )
			.then( () => {
				// Note: Deactivation is tracked on the backend (the `provider_extension_deactivated` event).
				createSuccessNotice(
					__(
						'The provider plugin was successfully deactivated.',
						'woocommerce'
					)
				);
				invalidateResolutionForStoreSelector( 'getPaymentProviders' );
				setIsDeactivating( false );
				onToggle();
			} )
			.catch( () => {
				recordPaymentsProviderEvent(
					'extension_deactivation_failed',
					provider,
					{
						reason: 'error',
					}
				);
				createErrorNotice(
					__(
						'Failed to deactivate the provider plugin.',
						'woocommerce'
					)
				);
				setIsDeactivating( false );
				onToggle();
			} );
	};

	/**
	 * Disables the provider from payment processing.
	 */
	const disableProvider = () => {
		const gatewayToggleNonce =
			window.woocommerce_admin.nonces?.gateway_toggle || '';

		if ( ! gatewayToggleNonce ) {
			recordPaymentsProviderEvent( 'disable_failed', provider, {
				reason: 'missing_nonce',
			} );
			createErrorNotice(
				__( 'Failed to disable the payments provider.', 'woocommerce' )
			);
			return;
		}
		setIsDisabling( true );
		togglePaymentGateway(
			provider.id,
			window.woocommerce_admin.ajax_url,
			gatewayToggleNonce
		)
			.then( () => {
				invalidateResolutionForStoreSelector( 'getPaymentProviders' );
				setIsDisabling( false );
				onToggle();
			} )
			.catch( () => {
				recordPaymentsProviderEvent( 'disable_failed', provider, {
					reason: 'error',
				} );
				createErrorNotice(
					__(
						'Failed to disable the payments provider.',
						'woocommerce'
					)
				);
				setIsDisabling( false );
				onToggle();
			} );
	};

	/**
	 * Hides the payments extension suggestion.
	 */
	const hideSuggestion = () => {
		const suggestionHideUrl = provider._links?.hide?.href;
		if ( ! suggestionHideUrl ) {
			createErrorNotice(
				__(
					'Failed to hide the payments extension suggestion.',
					'woocommerce'
				)
			);
			return;
		}

		setIsHidingSuggestion( true );

		hidePaymentExtensionSuggestion( suggestionHideUrl )
			.then( () => {
				invalidateResolutionForStoreSelector( 'getPaymentProviders' );
				setIsHidingSuggestion( false );
				onToggle();
			} )
			.catch( () => {
				createErrorNotice(
					__(
						'Failed to hide the payments extension suggestion.',
						'woocommerce'
					)
				);
				setIsHidingSuggestion( false );
				onToggle();
			} );
	};

	// Filter links in accordance with the gateway state.
	const contextLinks = links.filter( ( link: PaymentsProviderLink ) => {
		switch ( link._type ) {
			case 'pricing':
				// Show pricing link for any state.
				return true;
			case 'terms':
			case 'about':
				// Show terms and about links for gateways that are not enabled yet.
				return ! isEnabled;
			case 'documentation':
			case 'support':
				// Show documentation and support links for gateways are enabled.
				return isEnabled;
			default:
				return false;
		}
	} );

	return (
		<>
			{ contextLinks.map( ( link: PaymentsProviderLink ) => {
				const displayName = typeToDisplayName[ link._type ];
				return displayName ? (
					<div
						className="woocommerce-ellipsis-menu__content__item"
						key={ link._type }
					>
						<Button
							target="_blank"
							href={ link.url }
							onClick={ () => {
								// Record the event when user clicks on a provider's context link.
								recordPaymentsProviderEvent(
									'context_link_click',
									provider,
									{
										link_type: link._type,
										link_url: link.url,
									}
								);
							} }
						>
							{ displayName }
						</Button>
					</div>
				) : null;
			} ) }
			{ !! contextLinks.length && <CardDivider /> }
			{ isSuggestion && (
				<div
					className="woocommerce-ellipsis-menu__content__item"
					key="hide-suggestion"
				>
					<Button
						onClick={ () => {
							recordPaymentsProviderEvent(
								'context_link_click',
								provider,
								{
									link_type: 'hide_suggestion',
								}
							);
							hideSuggestion();
						} }
						isBusy={ isHidingSuggestion }
						disabled={ isHidingSuggestion }
					>
						{ __( 'Hide suggestion', 'woocommerce' ) }
					</Button>
				</div>
			) }
			{ ( canResetAccount || canResetOnboarding ) && (
				<div
					className="woocommerce-ellipsis-menu__content__item"
					key="reset-account"
				>
					<Button
						onClick={ () => {
							recordPaymentsProviderEvent(
								'context_link_click',
								provider,
								{
									link_type: 'reset_onboarding',
									with_account: canResetAccount, // Indicates if the reset is for an account or just for onboarding.
								}
							);
							setResetAccountModalVisible( true );
							onToggle();
						} }
						className={ 'components-button__danger' }
					>
						{ canResetAccount
							? __( 'Reset account', 'woocommerce' )
							: __( 'Reset onboarding', 'woocommerce' ) }
					</Button>
				</div>
			) }
			{ ! isSuggestion && ! isEnabled && (
				<div
					className="woocommerce-ellipsis-menu__content__item"
					key="deactivate"
				>
					<Button
						className={ 'components-button__danger' }
						onClick={ () => {
							recordPaymentsProviderEvent(
								'context_link_click',
								provider,
								{
									link_type: 'deactivate_extension',
								}
							);
							deactivateProviderExtension();
						} }
						isBusy={ isDeactivating }
						// If the plugin file is not available, or it's a bundled gateway, the button should be disabled.
						disabled={
							! pluginFile ||
							pluginFile === 'woocommerce/woocommerce' ||
							isDeactivating
						}
					>
						{ __( 'Deactivate', 'woocommerce' ) }
					</Button>
				</div>
			) }
			{ ! isSuggestion && isEnabled && (
				<div
					className="woocommerce-ellipsis-menu__content__item"
					key="disable"
				>
					<Button
						className={ 'components-button__danger' }
						onClick={ () => {
							recordPaymentsProviderEvent(
								'context_link_click',
								provider,
								{
									link_type: 'disable',
								}
							);
							disableProvider();
						} }
						isBusy={ isDisabling }
						disabled={ isDisabling }
					>
						{ __( 'Disable', 'woocommerce' ) }
					</Button>
				</div>
			) }
		</>
	);
};

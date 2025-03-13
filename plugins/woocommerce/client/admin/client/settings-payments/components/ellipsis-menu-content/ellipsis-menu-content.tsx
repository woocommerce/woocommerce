/**
 * External dependencies
 */
import { Button, CardDivider } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	pluginsStore,
	paymentSettingsStore,
	PaymentGatewayLink,
} from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './ellipsis-menu-content.scss';

interface EllipsisMenuContentProps {
	/**
	 * The ID of the payment provider.
	 */
	providerId: string;
	/**
	 * The main plugin file path of the plugin associated with the payment gateway.
	 */
	pluginFile: string;
	/**
	 * Indicates if the menu is being used for a payment extension suggestion.
	 */
	isSuggestion: boolean;
	/**
	 * The ID of the payment extension suggestion. Optional.
	 */
	suggestionId?: string;
	/**
	 * The URL to call when hiding a payment extension suggestion. Optional.
	 */
	suggestionHideUrl?: string;
	/**
	 * Callback to close the ellipsis menu.
	 */
	onToggle: () => void;
	/**
	 * Array of links related to the payment provider.
	 */
	links?: PaymentGatewayLink[];
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
}

/**
 * A component for rendering the content of an ellipsis menu in the WooCommerce payment settings.
 * The menu provides provider links and options to manage payment providers, such as enabling, disabling, deactivating gateways,
 * hiding suggestions, and resetting accounts.
 */
export const EllipsisMenuContent = ( {
	providerId,
	pluginFile,
	isSuggestion,
	suggestionId,
	suggestionHideUrl = '',
	onToggle,
	links = [],
	canResetAccount = false,
	setResetAccountModalVisible = () => {},
	isEnabled = false,
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
	 * Deactivates the payment gateway plugin.
	 */
	const deactivateGateway = () => {
		setIsDeactivating( true );
		deactivatePlugin( pluginFile )
			.then( () => {
				createSuccessNotice(
					__( 'Plugin was successfully deactivated.', 'woocommerce' )
				);
				invalidateResolutionForStoreSelector( 'getPaymentProviders' );
				setIsDeactivating( false );
				onToggle();
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to deactivate the plugin.', 'woocommerce' )
				);
				setIsDeactivating( false );
				onToggle();
			} );
	};

	/**
	 * Disables the payment gateway from payment processing.
	 */
	const disableGateway = () => {
		// Record the event when user clicks on a gateway's disable button.
		recordEvent( 'settings_payments_provider_disable_click', {
			provider_id: providerId,
		} );

		const gatewayToggleNonce =
			window.woocommerce_admin.nonces?.gateway_toggle || '';

		if ( ! gatewayToggleNonce ) {
			createErrorNotice(
				__( 'Failed to disable the plugin.', 'woocommerce' )
			);
			return;
		}
		setIsDisabling( true );
		togglePaymentGateway(
			providerId,
			window.woocommerce_admin.ajax_url,
			gatewayToggleNonce
		)
			.then( () => {
				// Record the event when user successfully disables a gateway.
				recordEvent( 'settings_payments_provider_disable', {
					provider_id: providerId,
				} );

				invalidateResolutionForStoreSelector( 'getPaymentProviders' );
				setIsDisabling( false );
				onToggle();
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to disable the plugin.', 'woocommerce' )
				);
				setIsDisabling( false );
				onToggle();
			} );
	};

	/**
	 * Hides the payment gateway suggestion.
	 */
	const hideSuggestion = () => {
		setIsHidingSuggestion( true );

		// Record the event before hiding the suggestion.
		recordEvent( 'settings_payments_recommendations_dismiss', {
			pes_id: suggestionId,
		} );
		hidePaymentExtensionSuggestion( suggestionHideUrl )
			.then( () => {
				invalidateResolutionForStoreSelector( 'getPaymentProviders' );
				setIsHidingSuggestion( false );
				onToggle();
			} )
			.catch( () => {
				createErrorNotice(
					__(
						'Failed to hide the payment extension suggestion.',
						'woocommerce'
					)
				);
				setIsHidingSuggestion( false );
				onToggle();
			} );
	};

	// Filter links in accordance with the gateway state.
	const contextLinks = links.filter( ( link: PaymentGatewayLink ) => {
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
			{ contextLinks.map( ( link: PaymentGatewayLink ) => {
				const displayName = typeToDisplayName[ link._type ];
				return displayName ? (
					<div
						className="woocommerce-ellipsis-menu__content__item"
						key={ link._type }
					>
						<Button target="_blank" href={ link.url }>
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
						onClick={ hideSuggestion }
						isBusy={ isHidingSuggestion }
						disabled={ isHidingSuggestion }
					>
						{ __( 'Hide suggestion', 'woocommerce' ) }
					</Button>
				</div>
			) }
			{ canResetAccount && (
				<div
					className="woocommerce-ellipsis-menu__content__item"
					key="reset-account"
				>
					<Button
						onClick={ () => {
							setResetAccountModalVisible( true );
							onToggle();
						} }
						className={ 'components-button__danger' }
					>
						{ __( 'Reset account', 'woocommerce' ) }
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
						onClick={ deactivateGateway }
						isBusy={ isDeactivating }
						disabled={ isDeactivating }
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
						onClick={ disableGateway }
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

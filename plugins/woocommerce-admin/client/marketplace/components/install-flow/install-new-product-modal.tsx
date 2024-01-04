/**
 * External dependencies
 */
import { ButtonGroup, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import { useState, useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import withModal from './withModal';
import ProductCard from '~/marketplace/components/product-card/product-card';
import { Product } from '~/marketplace/components/product-list/types';
import { installingStore } from '~/marketplace/contexts/install-store';
import { installProduct } from '~/marketplace/utils/functions';
import { Subscription } from '../my-subscriptions/types';
import { WP_ADMIN_PLUGIN_LIST_URL } from '../constants';
import { SubscriptionsContext } from '~/marketplace/contexts/subscriptions-context';

type InstallNewProductModalProps = {
	onClose: () => void;
	product?: Product;
};

function InstallNewProductModal( props: InstallNewProductModalProps ) {
	const [ isInstalling, setIsInstalling ] = useState( false );
	const [ installationDone, setInstallationDone ] = useState( false );
	const [ subscription, setSubscription ] = useState< Subscription >();
	const { subscriptions, refreshSubscriptions } =
		useContext( SubscriptionsContext );

	// TODO: Hit Woo.com Helper API endpoint for creating orders
	function order(): Promise< number > {
		return new Promise.resolve( 5278104 );
		return new Promise( ( resolve ) => {
			resolve( {
				product_key: 'wporg-5278104',
				product_id: 5278104,
				product_name: 'WooPayments',
				product_url: 'https://woo.com/products/woopayments/',
				product_icon:
					'https://woo.com/wp-content/uploads/2020/02/WooPayments-Icon.png',
				product_slug: 'woopayments',
				product_type: 'plugin',
				documentation_url: 'https://woo.com/document/woopayments/',
				zip_slug: 'woocommerce-payments',
				key_type: 'single',
				key_type_label: 'Single site',
				lifetime: true,
				product_status: 'publish',
				connections: [ 1823269 ],
				expires: 2147483647,
				expired: false,
				expiring: false,
				sites_max: 100,
				sites_active: 1,
				autorenew: false,
				maxed: false,
				is_installable: true,
				active: true,
				local: {
					installed: false,
					active: false,
					version: null,
					type: null,
					slug: null,
					path: null,
				},
				has_update: false,
				version: '6.9.2',
				subscription_available: false,
				subscription_installed: false,
			} );
		} );
	}

	function orderAndInstall() {
		setIsInstalling( true );

		return order().then( ( product_id ) => {
			// refresh subscriptions
			refreshSubscriptions( false ).then( () => {
				// Find product subscription
				subscriptions.
			} );
		} );
		// TODO: Issue tracks event
		return order()
			.then( ( subscriptionData ) => {
				setSubscription( subscriptionData );
				dispatch( installingStore ).startInstalling(
					subscriptionData.product_key
				);

				return installProduct( subscriptionData ).then( () => {
					dispatch( installingStore ).stopInstalling(
						subscriptionData.product_key
					);
					setInstallationDone( true );
				} );
			} )
			.finally( () => {
				setIsInstalling( false );
			} );
	}

	const beforeInstallModalContent = (
		<>
			<p className="woocommerce-marketplace__header-account-modal-text">
				{ __(
					'Would you like to install this extension?',
					'woocommerce'
				) }
			</p>
			{ props.product && (
				<ProductCard
					product={ props.product }
					small={ true }
					tracksData={ {
						position: 1,
						group: 'install-flow',
						label: 'install',
					} }
				/>
			) }

			<ButtonGroup className="woocommerce-marketplace__header-account-modal-button-group">
				<Button
					variant="tertiary"
					onClick={ props.onClose }
					className="woocommerce-marketplace__header-account-modal-button"
					key={ 'cancel' }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ orderAndInstall }
					// key={ 'install' }
					isBusy={ isInstalling }
					disabled={ isInstalling }
				>
					{ __( 'Install', 'woocommerce' ) }
				</Button>
			</ButtonGroup>
		</>
	);

	const successModalContent = (
		<>
			<p className="woocommerce-marketplace__header-account-modal-text">
				{ __(
					'Keep the momentum going and start setting up your extension.',
					'woocommerce'
				) }
			</p>
			{ props.product && (
				<ProductCard
					product={ props.product }
					small={ true }
					tracksData={ {
						position: 1,
						group: 'subscriptions',
						label: 'install',
					} }
				/>
			) }

			<ButtonGroup className="woocommerce-marketplace__header-account-modal-button-group">
				{ subscription?.documentation_url && (
					<Button
						variant="tertiary"
						href={ subscription?.documentation_url }
						className="woocommerce-marketplace__header-account-modal-button"
						key={ 'docs' }
					>
						{ __( 'View Docs', 'woocommerce' ) }
					</Button>
				) }
				<Button
					variant="primary"
					href={ WP_ADMIN_PLUGIN_LIST_URL }
					className="woocommerce-marketplace__header-account-modal-button"
					key={ 'plugin-list' }
				>
					{ __( 'View in Plugins', 'woocommerce' ) }
				</Button>
			</ButtonGroup>
		</>
	);

	if ( installationDone ) {
		return withModal(
			successModalContent,
			__( 'Installation done!', 'woocommerce' ),
			props.onClose
		);
	}

	return withModal(
		beforeInstallModalContent,
		__( 'Add to store', 'woocommerce' ),
		props.onClose
	);
}

export default InstallNewProductModal;

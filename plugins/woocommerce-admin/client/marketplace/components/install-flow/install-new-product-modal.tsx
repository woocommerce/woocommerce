/**
 * External dependencies
 */
import { ButtonGroup, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { navigateTo, getNewPath, useQuery } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import withModal from './withModal';
import ProductCard from '~/marketplace/components/product-card/product-card';
import { Product } from '~/marketplace/components/product-list/types';
import { installingStore } from '~/marketplace/contexts/install-store';
import { createOrder, downloadProduct } from '~/marketplace/utils/functions';
import { Subscription } from '../my-subscriptions/types';
import { getAdminSetting } from '~/utils/admin-settings';
import ConnectModal from './connect-modal';
import ProductInstalledModal from './product-installed-modal';
import { MARKETPLACE_PATH } from '~/marketplace/components/constants';

enum InstallStatus {
	'notInstalled',
	'installing',
	'installed',
	'failed',
}

function InstallNewProductModal( props: { products: Product[] } ) {
	const [ installStatus, setInstallStatus ] = useState< InstallStatus >(
		InstallStatus.notInstalled
	);
	const [ subscription, setSubscription ] = useState< Subscription >();
	const [ product, setProduct ] = useState< Product >();
	const [ isConnected, setIsConnected ] = useState< boolean >();
	const [ showModal, setShowModal ] = useState< boolean >( false );

	const query = useQuery();

	// Check if the store is connected to Woo.com.
	useEffect( () => {
		const wccomSettings = getAdminSetting( 'wccomHelper', {} );
		setIsConnected( wccomSettings?.isConnected );
	}, [] );

	// Listen for changes in the query, and show the modal if the installProduct query param is set.
	useEffect( () => {
		if ( ! query.installProduct ) {
			setShowModal( false );
			return;
		}

		const productId = parseInt( query.installProduct, 10 );

		/**
		 * Try to find the product in the search results. We need to product to be able to
		 * show the title and the icon.
		 */
		const productToInstall = props.products.find(
			( item ) => item.id === productId
		);

		if ( ! productToInstall ) {
			setShowModal( false );
			return;
		}

		setShowModal( true );
		setProduct( productToInstall );
	}, [ query, props.products ] );

	function orderAndInstall() {
		if ( ! product || ! product.id ) {
			return;
		}

		recordEvent( 'marketplace_install_new_product_clicked', {
			product_id: product.id,
		} );

		setInstallStatus( InstallStatus.installing );

		return createOrder( product.id )
			.then( ( response: string ) => {
				const subscriptionData: Subscription = JSON.parse( response );

				setSubscription( subscriptionData );

				dispatch( installingStore ).startInstalling(
					subscriptionData.product_key
				);

				return downloadProduct( subscriptionData ).then( () => {
					dispatch( installingStore ).stopInstalling(
						subscriptionData.product_key
					);
					setInstallStatus( InstallStatus.installed );
				} );
			} )
			.catch( () => {
				setInstallStatus( InstallStatus.failed );
			} );
	}

	function onClose() {
		navigateTo( {
			url: getNewPath(
				{
					...query,
					install: undefined,
					installProduct: undefined,
				},
				MARKETPLACE_PATH,
				{}
			),
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
			{ product && (
				<ProductCard
					product={ product }
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
					onClick={ onClose }
					className="woocommerce-marketplace__header-account-modal-button"
					key={ 'cancel' }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ orderAndInstall }
					// key={ 'install' }
					isBusy={ installStatus === InstallStatus.installing }
					disabled={ installStatus === InstallStatus.installing }
				>
					{ __( 'Install', 'woocommerce' ) }
				</Button>
			</ButtonGroup>
		</>
	);

	if ( ! product && ! showModal ) {
		return <></>;
	}

	if ( ! isConnected ) {
		return withModal(
			<ConnectModal product={ product } installingProductKey="TODO" />,
			__( 'Install', 'woocommerce' ),
			onClose
		);
	}

	if ( installStatus === InstallStatus.installed ) {
		return withModal(
			<ProductInstalledModal
				product={ product }
				documentationUrl={ subscription?.documentation_url }
			/>,
			__( 'Install', 'woocommerce' ),
			onClose
		);
	}

	return withModal(
		beforeInstallModalContent,
		__( 'Install', 'woocommerce' ),
		onClose
	);
}

export default InstallNewProductModal;

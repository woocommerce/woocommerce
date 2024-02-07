/**
 * External dependencies
 */
import { ButtonGroup, Button, Modal, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import { useState, useEffect, useContext } from '@wordpress/element';
import { navigateTo, getNewPath, useQuery } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { Status } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import ProductCard from '../product-card/product-card';
import { Product } from '../product-list/types';
import ConnectAccountButton from '../my-subscriptions/table/actions/connect-account-button';
import { installingStore } from '../../contexts/install-store';
import { downloadProduct } from '../../utils/functions';
import { createOrder } from './create-order';
import { MARKETPLACE_PATH, WP_ADMIN_PLUGIN_LIST_URL } from '../constants';
import { getAdminSetting } from '../../../utils/admin-settings';
import { MarketplaceContext } from '../../contexts/marketplace-context';

enum InstallFlowStatus {
	'notConnected',
	'notInstalled',
	'installing',
	'installedCanActivate',
	'installedCannotActivate',
	'installFailed',
	'activating',
	'activated',
	'activationFailed',
}

function InstallNewProductModal( props: { products: Product[] } ) {
	const [ installStatus, setInstallStatus ] = useState< InstallFlowStatus >(
		InstallFlowStatus.notInstalled
	);
	const [ product, setProduct ] = useState< Product >();
	const [ installedProducts, setInstalledProducts ] = useState< string[] >();
	const [ isStoreConnected, setIsStoreConnected ] = useState< boolean >();
	const [ activateUrl, setActivateUrl ] = useState< string >();
	const [ documentationUrl, setDocumentationUrl ] = useState< string >();
	const [ showModal, setShowModal ] = useState< boolean >( false );
	const [ notice, setNotice ] = useState< {
		message: string;
		status: Status;
	} >();
	const { addInstalledProduct } = useContext( MarketplaceContext );

	const query = useQuery();

	// Check if the store is connected to Woo.com. This is run once, when the component is mounted.
	useEffect( () => {
		const wccomSettings = getAdminSetting( 'wccomHelper', {} );

		setInstalledProducts( wccomSettings?.installedProducts );
		setIsStoreConnected( wccomSettings?.isConnected );
	}, [] );

	/**
	 * Listen for changes in the query, and show the modal if the installProduct query param is set.
	 * If it's set, try to find the product in the products prop. We need it to be able to
	 * display title, icon and send product ID to Woo.com to create an order.
	 */
	useEffect( () => {
		setShowModal( false );
		if ( ! query.installProduct ) {
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
			return;
		}

		if ( installedProducts ) {
			const isInstalled = !! installedProducts.find(
				( item ) => item === productToInstall.slug
			);

			if ( isInstalled ) {
				return;
			}
		}

		if ( ! isStoreConnected ) {
			setInstallStatus( InstallFlowStatus.notConnected );
			setNotice( {
				status: 'warning',
				message: __(
					'In order to install a product, you need to first connect your account.',
					'woocommerce'
				),
			} );
		} else {
			setInstallStatus( InstallFlowStatus.notInstalled );
		}

		setShowModal( true );
		setProduct( productToInstall );
	}, [ query, props.products, installedProducts, isStoreConnected ] );

	/**
	 * WordPress gives us a activateURL as a response to us installig the product.
	 * Even though it's not an API endpoint, we can hit that URL with fetch
	 * and activate the plugin.
	 */
	function activateClick() {
		if ( ! activateUrl ) {
			return;
		}

		setInstallStatus( InstallFlowStatus.activating );

		recordEvent( 'marketplace_activate_new_product_clicked', {
			product_id: product ? product.id : 0,
		} );

		fetch( activateUrl )
			.then( () => {
				setInstallStatus( InstallFlowStatus.activated );
			} )
			.catch( () => {
				setInstallStatus( InstallFlowStatus.activationFailed );
				setNotice( {
					status: 'error',
					message: __(
						'Activation failed. Please try again from the plugins page.',
						'woocommerce'
					),
				} );
			} );
	}

	function orderAndInstall() {
		if ( ! product || ! product.id ) {
			return;
		}

		recordEvent( 'marketplace_install_new_product_clicked', {
			product_id: product.id,
		} );

		setInstallStatus( InstallFlowStatus.installing );

		createOrder( product.id )
			.then( ( response ) => {
				// This narrows the CreateOrderResponse type to CreateOrderSuccessResponse
				if ( ! response.success ) {
					throw response;
				}

				dispatch( installingStore ).startInstalling( product.id );
				setDocumentationUrl( response.data.documentation_url );

				if ( product.slug ) {
					addInstalledProduct( product.slug ?? '' );
				}

				return downloadProduct(
					response.data.product_type,
					response.data.zip_slug
				).then( ( downloadResponse ) => {
					dispatch( installingStore ).stopInstalling( product.id );

					// No activateUrl means we can't activate the plugin.
					if ( downloadResponse.data.activateUrl ) {
						setActivateUrl( downloadResponse.data.activateUrl );

						setInstallStatus(
							InstallFlowStatus.installedCanActivate
						);
					} else {
						setInstallStatus(
							InstallFlowStatus.installedCannotActivate
						);
					}
				} );
			} )
			.catch( ( error ) => {
				/**
				 * apiFetch doesn't return the HTTP error code in the error condition.
				 * We'll rely on the data returned by the server.
				 */
				if ( error.data.redirect_location ) {
					setNotice( {
						status: 'warning',
						message: __(
							'We need your address to complete installing this product. We will redirect you to Woo.com checkout. Afterwards, you will be able to install the product.',
							'woocommerce'
						),
					} );

					// Wait to allow users to read the notice.
					setTimeout( () => {
						window.location.href = error.data.redirect_location;
					}, 5000 );
				} else {
					setInstallStatus( InstallFlowStatus.installFailed );
					setNotice( {
						status: 'error',
						message:
							error.data.message ??
							__(
								'An error occurred. Please try again later.',
								'woocommerce'
							),
					} );
				}
			} );
	}

	function onClose() {
		setInstallStatus( InstallFlowStatus.notInstalled );
		setNotice( undefined );

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

	function getTitle(): string {
		if ( installStatus === InstallFlowStatus.activated ) {
			return __( 'You are ready to go!', 'woocommerce' );
		}

		return __( 'Add to Store', 'woocommerce' );
	}

	function getDescription(): string {
		if ( installStatus === InstallFlowStatus.notConnected ) {
			return '';
		}

		if (
			installStatus === InstallFlowStatus.installedCanActivate ||
			installStatus === InstallFlowStatus.activating
		) {
			return __(
				'Extension successfully installed. Would you like to activate it?',
				'woocommerce'
			);
		}

		if ( installStatus === InstallFlowStatus.installedCannotActivate ) {
			return __(
				"Extension successfully installed but we can't activate it at the moment. Please visit the plugins page to see more.",
				'woocommerce'
			);
		}

		if ( installStatus === InstallFlowStatus.activated ) {
			return __(
				'Keep the momentum going and start setting up your extension.',
				'woocommerce'
			);
		}

		return __( 'Would you like to install this extension?', 'woocommerce' );
	}

	function secondaryButton(): React.ReactElement {
		if ( installStatus === InstallFlowStatus.activated ) {
			if ( documentationUrl ) {
				return (
					<Button
						variant="tertiary"
						href={ documentationUrl }
						className="woocommerce-marketplace__header-account-modal-button"
						key={ 'docs' }
					>
						{ __( 'View Docs', 'woocommerce' ) }
					</Button>
				);
			}

			return <></>;
		}

		return (
			<Button
				variant="tertiary"
				onClick={ onClose }
				className="woocommerce-marketplace__header-account-modal-button"
				key={ 'cancel' }
			>
				{ __( 'Cancel', 'woocommerce' ) }
			</Button>
		);
	}

	function primaryButton(): React.ReactElement {
		if ( installStatus === InstallFlowStatus.notConnected ) {
			return <ConnectAccountButton variant="primary" key={ 'connect' } />;
		}

		if (
			installStatus === InstallFlowStatus.installedCanActivate ||
			installStatus === InstallFlowStatus.activating
		) {
			return (
				<Button
					variant="primary"
					onClick={ activateClick }
					key={ 'activate' }
					isBusy={ installStatus === InstallFlowStatus.activating }
					disabled={ installStatus === InstallFlowStatus.activating }
				>
					{ __( 'Activate', 'woocommerce' ) }
				</Button>
			);
		}

		if (
			installStatus === InstallFlowStatus.activated ||
			installStatus === InstallFlowStatus.installedCannotActivate ||
			installStatus === InstallFlowStatus.activationFailed
		) {
			return (
				<Button
					variant="primary"
					href={ WP_ADMIN_PLUGIN_LIST_URL }
					className="woocommerce-marketplace__header-account-modal-button"
					key={ 'plugin-list' }
				>
					{ __( 'View in Plugins', 'woocommerce' ) }
				</Button>
			);
		}

		return (
			<Button
				variant="primary"
				onClick={ orderAndInstall }
				key={ 'install' }
				isBusy={ installStatus === InstallFlowStatus.installing }
				disabled={
					installStatus === InstallFlowStatus.installing ||
					installStatus === InstallFlowStatus.installFailed
				}
			>
				{ __( 'Install', 'woocommerce' ) }
			</Button>
		);
	}

	/**
	 * Actually, just checking showModal is enough here. However, checking
	 * for the product narrows the type from "Product | undefined"
	 * to "Product".
	 */
	if ( ! product || ! showModal ) {
		return <></>;
	}

	return (
		<Modal
			title={ getTitle() }
			onRequestClose={ onClose }
			focusOnMount={ true }
			className="woocommerce-marketplace__header-account-modal has-size-medium"
			style={ { borderRadius: 4 } }
			overlayClassName="woocommerce-marketplace__header-account-modal-overlay"
		>
			{ notice && (
				<Notice status={ notice.status } isDismissible={ false }>
					{ notice.message }
				</Notice>
			) }
			<p className="woocommerce-marketplace__header-account-modal-text">
				{ getDescription() }
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
				{ secondaryButton() }
				{ primaryButton() }
			</ButtonGroup>
		</Modal>
	);
}

export default InstallNewProductModal;

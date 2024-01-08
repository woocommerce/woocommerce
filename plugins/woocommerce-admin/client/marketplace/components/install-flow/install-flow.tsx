/**
 * External dependencies
 */
import { useQuery, getNewPath, navigateTo } from '@woocommerce/navigation';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ConnectModal from './connect-modal';
import InstallNewProductModal from '~/marketplace/components/install-flow/install-new-product-modal';
import InstallSubscriptionModal from '~/marketplace/components/install-flow/install-subscription-modal';
import { MARKETPLACE_PATH } from '~/marketplace/components/constants';
import { getAdminSetting } from '~/utils/admin-settings';
import { Product } from '~/marketplace/components/product-list/types';

function InstallFlow( props: { products: Product[] } ) {
	const [ showConnectModal, setShowConnectModal ] = useState( false );
	const [ showInstallNewProductModal, setShowInstallNewProductModal ] =
		useState( false );
	const [ showInstallSubscriptionModal, setShowInstallSubscriptionModal ] =
		useState( false );
	const [ isConnected, setIsConnected ] = useState< boolean >();
	const [ productKey, setProductKey ] = useState< string >();
	const [ product, setProduct ] = useState< Product >();
	const query = useQuery();

	useEffect( () => {
		const wccomSettings = getAdminSetting( 'wccomHelper', {} );
		setIsConnected( wccomSettings?.isConnected );
	}, [] );

	useEffect( () => {
		if ( ! query.install && ! query.installProduct ) {
			return;
		}

		// If connected but install param is "new", we install new product
		if ( query.installProduct ) {
			const productId = parseInt( query.installProduct, 10 );

			if ( ! productId ) {
				return;
			}

			const productToInstall = props.products.find(
				( item ) => item.id === productId
			);

			if ( ! productToInstall ) {
				return;
			}

			setProduct( productToInstall );

			if ( ! isConnected ) {
				setShowConnectModal( true );
			} else {
				setShowInstallNewProductModal( true );
			}

			return;
		}

		if ( query.install ) {
			// Find subscription
		}

		setShowInstallSubscriptionModal( true );
		setProductKey( query.install );
	}, [ query, isConnected, props.products ] );

	function onClose() {
		setShowConnectModal( false );
		setShowInstallNewProductModal( false );
		setShowInstallSubscriptionModal( false );

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

	return <>Test.</>;
}

export default InstallFlow;

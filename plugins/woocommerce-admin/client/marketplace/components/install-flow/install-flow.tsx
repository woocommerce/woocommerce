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

type ProductInfo = {
	id: number;
	name: string;
	icon: string;
};

function InstallFlow() {
	const [ showConnectModal, setShowConnectModal ] = useState( false );
	const [ showInstallNewProductModal, setShowInstallNewProductModal ] =
		useState( false );
	const [ showInstallSubscriptionModal, setShowInstallSubscriptionModal ] =
		useState( false );
	const [ productInfo, setProductInfo ] = useState< ProductInfo >();
	const [ isConnected, setIsConnected ] = useState< boolean >();
	const [ productKey, setProductKey ] = useState< string >();
	const query = useQuery();

	useEffect( () => {
		const wccomSettings = getAdminSetting( 'wccomHelper', {} );
		setIsConnected( wccomSettings?.isConnected );
	}, [] );

	useEffect( () => {
		if ( ! query.install ) {
			return;
		}

		// If not connected, show connect modal
		if ( ! isConnected ) {
			setShowConnectModal( true );

			return;
		}

		// If connected but install param is "new", we install new product
		if ( query.install === 'new' ) {
			setShowInstallNewProductModal( true );
			setProductInfo( {
				id: parseInt( query.product_id, 10 ),
				name: query.product_name,
				icon: query.product_icon,
			} );
			return;
		}

		// If connected but install param is not "new" we install from existing subscriptions
		setShowInstallSubscriptionModal( true );
		setProductKey( query.install );
	}, [ query, isConnected ] );

	function onClose() {
		setShowConnectModal( false );
		setShowInstallNewProductModal( false );
		setShowInstallSubscriptionModal( false );

		navigateTo( {
			url: getNewPath(
				{
					...query,
					install: undefined,
				},
				MARKETPLACE_PATH,
				{}
			),
		} );
	}

	return (
		<>
			{ showConnectModal && (
				<ConnectModal
					productInfo={ productInfo }
					onClose={ onClose }
					installingProductKey={ query?.install }
				/>
			) }
			{ showInstallNewProductModal && (
				<InstallNewProductModal
					productInfo={ product }
					onClose={ onClose }
				/>
			) }
			{ showInstallSubscriptionModal && (
				<InstallSubscriptionModal
					product={ product }
					onClose={ onClose }
					productKey={ productKey ?? '' }
				/>
			) }
		</>
	);
}

export default InstallFlow;

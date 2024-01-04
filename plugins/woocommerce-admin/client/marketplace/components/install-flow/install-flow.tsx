/**
 * External dependencies
 */
import { useQuery, getNewPath, navigateTo } from '@woocommerce/navigation';
import { useContext, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ConnectModal from './connect-modal';
import { InstallFlowContext } from '~/marketplace/contexts/install-flow-context';
import InstallNewProductModal from '~/marketplace/components/install-flow/install-new-product-modal';
import InstallSubscriptionModal from '~/marketplace/components/install-flow/install-subscription-modal';
import { MARKETPLACE_PATH } from '~/marketplace/components/constants';

function InstallFlow() {
	const [ showConnectModal, setShowConnectModal ] = useState( false );
	const [ showInstallNewProductModal, setShowInstallNewProductModal ] =
		useState( false );
	const [ showInstallSubscriptionModal, setShowInstallSubscriptionModal ] =
		useState( false );
	const [ productKey, setProductKey ] = useState< string >();
	const query = useQuery();
	const { isConnected, product } = useContext( InstallFlowContext );

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
					product={ product }
					onClose={ onClose }
					installingProductKey={ query?.install }
				/>
			) }
			{ showInstallNewProductModal && (
				<InstallNewProductModal
					product={ product }
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

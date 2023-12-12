/**
 * External dependencies
 */
import { Button, ButtonGroup, Modal, Notice } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { getNewPath, navigateTo, useQuery } from '@woocommerce/navigation';
import {
	useCallback,
	useContext,
	useEffect,
	useState,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Subscription } from '../../types';
import { getAdminSetting } from '../../../../../utils/admin-settings';
import Install from './install';
import { SubscriptionsContext } from '../../../../contexts/subscriptions-context';
import { MARKETPLACE_PATH } from '../../../constants';
import ConnectAccountButton from './connect-account-button';
import ProductCard from '../../../product-card/product-card';
import { addNotice, subscriptionToProduct } from '../../../../utils/functions';
import { NoticeStatus } from '../../../../contexts/types';

export default function InstallModal() {
	const query = useQuery();
	const installingProductKey = query?.install;

	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	const isConnected = !! wccomSettings?.isConnected;

	const [ showModal, setShowModal ] = useState< boolean >( false );

	const { subscriptions, isLoading } = useContext( SubscriptionsContext );

	const subscription: Subscription | undefined = subscriptions.find(
		( s: Subscription ) => s.product_key === installingProductKey
	);

	const removeInstallQuery = useCallback( () => {
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
	}, [ query ] );

	useEffect( () => {
		if ( isLoading ) {
			return;
		}

		// If subscriptions loaded, but we don't have a subscription for the product key, show an error.
		if (
			installingProductKey &&
			isConnected &&
			! isLoading &&
			! subscription
		) {
			addNotice(
				installingProductKey,
				sprintf(
					/* translators: %s is the product key */
					__(
						'Could not find subscription with product key %s.',
						'woocommerce'
					),
					installingProductKey
				),
				NoticeStatus.Error
			);
			removeInstallQuery();
		} else {
			setShowModal( !! installingProductKey );
		}
	}, [
		isConnected,
		isLoading,
		installingProductKey,
		removeInstallQuery,
		subscription,
	] );

	const actionButton = () => {
		if ( ! isConnected ) {
			return (
				<ConnectAccountButton
					variant="primary"
					install={ installingProductKey }
				/>
			);
		} else if ( subscription ) {
			return <Install subscription={ subscription } variant="primary" />;
		}
	};

	const modalContent = () => {
		if ( ! isConnected ) {
			return (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'In order to install a product, you need to first connect your account.',
						'woocommerce'
					) }
				</Notice>
			);
		} else if ( subscription ) {
			return (
				<ProductCard
					product={ subscriptionToProduct( subscription ) }
					small={ true }
					tracksData={ {
						position: 1,
						group: 'subscriptions',
						label: 'install',
					} }
				/>
			);
		}
	};

	const onClose = () => {
		removeInstallQuery();
		setShowModal( false );
	};

	if ( ! showModal ) {
		return null;
	}

	return (
		<Modal
			title={ __( 'Add to store', 'woocommerce' ) }
			onRequestClose={ onClose }
			focusOnMount={ true }
			className="woocommerce-marketplace__header-account-modal"
			style={ { borderRadius: 4 } }
			overlayClassName="woocommerce-marketplace__header-account-modal-overlay"
		>
			{ modalContent() }
			<ButtonGroup className="woocommerce-marketplace__header-account-modal-button-group">
				<Button
					variant="tertiary"
					onClick={ onClose }
					className="woocommerce-marketplace__header-account-modal-button"
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				{ actionButton() }
			</ButtonGroup>
		</Modal>
	);
}

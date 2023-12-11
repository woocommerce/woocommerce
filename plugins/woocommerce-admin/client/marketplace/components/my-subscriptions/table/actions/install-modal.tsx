/**
 * External dependencies
 */
import { Button, ButtonGroup, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { getNewPath, navigateTo, useQuery } from '@woocommerce/navigation';
import { useContext, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Subscription } from '../../types';
import { getAdminSetting } from '../../../../../utils/admin-settings';
import Install from './install';
import { SubscriptionsContext } from '../../../../contexts/subscriptions-context';
import { MARKETPLACE_PATH } from '../../../constants';
import ConnectAccountButton from './connect-account-button';

export default function InstallModal() {
	const query = useQuery();
	const installingProductKey = query?.install;

	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	const isConnected = !! wccomSettings?.isConnected;

	const [ showModal, setShowModal ] = useState< boolean >( false );

	const { subscriptions } = useContext( SubscriptionsContext );

	useEffect( () => {
		setShowModal( !! query?.install );
	}, [ query?.install ] );

	if ( ! showModal ) {
		return null;
	}

	const subscription: Subscription | undefined = subscriptions.find(
		( s: Subscription ) => s.product_key === installingProductKey
	);

	const actionButton = () => {
		if ( isConnected && subscription ) {
			return <Install subscription={ subscription } />;
		}

		return <ConnectAccountButton variant="primary" />;
	};

	const onClose = () => {
		const newQuery = {
			...query,
			install: undefined,
		};
		navigateTo( {
			url: getNewPath( newQuery, MARKETPLACE_PATH, {} ),
		} );

		setShowModal( false );
	};

	return (
		<Modal
			title={ __( 'Add to store', 'woocommerce' ) }
			onRequestClose={ onClose }
			focusOnMount={ true }
			className="woocommerce-marketplace__header-account-modal"
			style={ { borderRadius: 4 } }
			overlayClassName="woocommerce-marketplace__header-account-modal-overlay"
		>
			<p className="woocommerce-marketplace__header-account-modal-text"></p>
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

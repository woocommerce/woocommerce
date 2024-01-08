/**
 * External dependencies
 */
import { ButtonGroup, Button, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ConnectAccountButton from '../my-subscriptions/table/actions/connect-account-button';
import withModal from './withModal';
import ProductCard from '~/marketplace/components/product-card/product-card';
import { Product } from '~/marketplace/components/product-list/types';

type ConnectModalProps = {
	installingProductKey: string;
	product?: Product;
};

function ConnectModal( props: ConnectModalProps ) {
	return (
		<>
			{ props.product && (
				<ProductCard
					product={ props.product }
					small={ true }
					tracksData={ {
						position: 1,
						group: 'install-flow',
						label: 'connect',
					} }
				/>
			) }
			<Notice status="warning" isDismissible={ false }>
				{ __(
					'In order to install a product, you need to first connect your account.',
					'woocommerce'
				) }
			</Notice>
			<ButtonGroup className="woocommerce-marketplace__header-account-modal-button-group">
				<Button
					variant="tertiary"
					className="woocommerce-marketplace__header-account-modal-button"
					key={ 'cancel' }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<ConnectAccountButton
					variant="primary"
					install={ props.installingProductKey }
					key={ 'connect' }
				/>
			</ButtonGroup>
		</>
	);
}

export default ConnectModal;

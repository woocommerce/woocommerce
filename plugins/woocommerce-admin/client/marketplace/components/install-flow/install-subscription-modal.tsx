/**
 * External dependencies
 */
import { ButtonGroup, Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useContext, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import withModal from './withModal';
import ProductCard from '~/marketplace/components/product-card/product-card';
import { Product } from '~/marketplace/components/product-list/types';
import Install from '../my-subscriptions/table/actions/install';
import { SubscriptionsContext } from '~/marketplace/contexts/subscriptions-context';
import { Subscription } from '~/marketplace/components/my-subscriptions/types';
import { NoticeStatus } from '../../contexts/types';
import { addNotice, subscriptionToProduct } from '../../utils/functions';
import { WP_ADMIN_PLUGIN_LIST_URL } from '../constants';

type InstallSubscriptionModalProps = {
	onClose: () => void;
	productKey: string;
};

function InstallSubscriptionModal( props: InstallSubscriptionModalProps ) {
	const { subscriptions, isLoading } = useContext( SubscriptionsContext );
	const [ subscription, setSubscription ] = useState< Subscription >();
	const [ isInstalled, setIsInstalled ] = useState< boolean >( false );
	const [ product, setProduct ] = useState< Product >();

	useEffect( () => {
		if ( isLoading ) {
			return;
		}

		const subscriptionToInstall: Subscription | undefined =
			subscriptions.find(
				( s: Subscription ) => s.product_key === props.productKey
			);

		if ( ! subscriptionToInstall ) {
			addNotice(
				props.productKey,
				sprintf(
					/* translators: %s is the product key */
					__(
						'Could not find subscription with product key %s.',
						'woocommerce'
					),
					props.productKey
				),
				NoticeStatus.Error
			);

			props.onClose();

			return;
		}

		setProduct( subscriptionToProduct( subscriptionToInstall ) );
		setSubscription( subscriptionToInstall );
	}, [ isLoading, subscription, props, subscriptions ] );

	useEffect( () => {
		if ( subscription && subscription.local.installed ) {
			setIsInstalled( true );
		}
	}, [ subscription ] );

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
					onClick={ props.onClose }
					className="woocommerce-marketplace__header-account-modal-button"
					key={ 'cancel' }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				{ subscription && (
					<Install
						subscription={ subscription }
						variant="primary"
						onError={ props.onClose }
						key={ 'install' }
					/>
				) }
			</ButtonGroup>
		</>
	);

	const successModalContent = (
		<>
			<p className="woocommerce-marketplace__header-account-modal-text">
				{ __( 'You are ready to go!', 'woocommerce' ) }
			</p>
			{ product && (
				<ProductCard
					product={ product }
					small={ true }
					tracksData={ {
						position: 1,
						group: 'subscriptions',
						label: 'install',
					} }
				/>
			) }

			<ButtonGroup className="woocommerce-marketplace__header-account-modal-button-group">
				<Button
					variant="secondary"
					href={ subscription?.documentation_url }
					target="_blank"
					className="woocommerce-marketplace__header-account-modal-button"
					key={ 'docs' }
				>
					{ __( 'View docs', 'woocommerce' ) }
				</Button>
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

	if ( isLoading ) {
		return <></>;
	}

	if ( isInstalled ) {
		return withModal(
			successModalContent,
			__( 'You are ready to go!', 'woocommerce' ),
			props.onClose
		);
	}

	return withModal(
		beforeInstallModalContent,
		__( 'Add to store', 'woocommerce' ),
		props.onClose
	);
}

export default InstallSubscriptionModal;

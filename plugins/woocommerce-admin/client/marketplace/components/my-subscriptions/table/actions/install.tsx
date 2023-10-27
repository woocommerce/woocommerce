/**
 * External dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useContext } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SubscriptionsContext } from '../../../../contexts/subscriptions-context';
import { installProduct } from '../../../../utils/functions';
import { Subscription } from '../../types';
import { InstallContext } from '../../../../contexts/install-context';

interface InstallProps {
	subscription: Subscription;
}

export default function Install( props: InstallProps ) {
	const { createWarningNotice, createSuccessNotice } =
		useDispatch( 'core/notices' );
	const { loadSubscriptions } = useContext( SubscriptionsContext );
	const { isInstalling, addInstalling, removeInstalling } =
		useContext( InstallContext );
	const loading = isInstalling( props.subscription.product_key );

	const install = () => {
		addInstalling( props.subscription.product_key );
		installProduct( props.subscription )
			.then( () => {
				loadSubscriptions( false ).then( () => {
					createSuccessNotice(
						sprintf(
							// translators: %s is the product name.
							__( '%s successfully installed.', 'woocommerce' ),
							props.subscription.product_name
						),
						{
							icon: <Icon icon="yes" />,
						}
					);
					removeInstalling( props.subscription.product_key );
				} );
			} )
			.catch( ( error ) => {
				loadSubscriptions( false ).then( () => {
					let errorMessage = sprintf(
						// translators: %s is the product name.
						__( '%s couldn’t be installed.', 'woocommerce' ),
						props.subscription.product_name
					);
					if ( error?.success === false && error?.data.message ) {
						errorMessage += ' ' + error.data.message;
					}
					createWarningNotice( errorMessage, {
						actions: [
							{
								label: __( 'Try again', 'woocommerce' ),
								onClick: install,
							},
						],
					} );
					removeInstalling( props.subscription.product_key );
				} );
			} );
	};

	function installButtonLabel( installing: boolean ) {
		if ( installing ) {
			return __( 'Installing…', 'woocommerce' );
		}

		return __( 'Install', 'woocommerce' );
	}

	return (
		<Button
			variant="link"
			isBusy={ loading }
			disabled={ loading }
			onClick={ install }
		>
			{ installButtonLabel( loading ) }
		</Button>
	);
}

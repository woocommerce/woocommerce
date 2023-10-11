/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';
import { useContext, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { Subscription } from './types';
import { installProduct } from '../../utils/functions';
import { SubscriptionsContext } from '../../contexts/subscriptions-context';

interface ActivationToggleProps {
	subscription: Subscription;
}

export default function Install( props: ActivationToggleProps ) {
	const [ loading, setLoading ] = useState( false );
	const { createWarningNotice, createSuccessNotice } =
		useDispatch( 'core/notices' );
	const { loadSubscriptions } = useContext( SubscriptionsContext );

	const install = () => {
		setLoading( true );
		installProduct( props.subscription.product_key )
			.then( () => {
				loadSubscriptions( false );
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
			} )
			.catch( () => {
				createWarningNotice(
					sprintf(
						// translators: %s is the product name.
						__( '%s couldn’t be installed.', 'woocommerce' ),
						props.subscription.product_name
					),
					{
						actions: [
							{
								label: __( 'Try again', 'woocommerce' ),
								onClick: install,
							},
						],
					}
				);
			} )
			.finally( () => {
				setLoading( false );
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
			variant="primary"
			isBusy={ loading }
			disabled={ loading }
			onClick={ install }
		>
			{ installButtonLabel( loading ) }
		</Button>
	);
}

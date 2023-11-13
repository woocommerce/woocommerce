/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useContext, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import RefreshIcon from '../../../../assets/images/refresh.svg';
import { SubscriptionsContext } from '../../../../contexts/subscriptions-context';

export function RefreshButton() {
	const { refreshSubscriptions } = useContext( SubscriptionsContext );
	const [ isLoading, setIsLoading ] = useState( false );

	const onClick = () => {
		if ( isLoading ) {
			return;
		}
		setIsLoading( true );
		refreshSubscriptions().finally( () => {
			setIsLoading( false );
		} );
	};

	return (
		<Button
			className="woocommerce-marketplace__refresh-subscriptions"
			onClick={ onClick }
			isBusy={ isLoading }
		>
			<img
				src={ RefreshIcon }
				alt={ __( 'Refresh subscriptions', 'woocommerce' ) }
				className="woocommerce-marketplace__refresh-subscriptions__icon"
			/>
			{ __( 'Refresh', 'woocommerce' ) }
		</Button>
	);
}

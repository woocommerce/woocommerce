/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useContext, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import RefreshIcon from '../../../../assets/images/refresh.svg';
import { SubscriptionsContext } from '../../../../contexts/subscriptions-context';
import { addNotice, removeNotice } from '../../../../utils/functions';
import { NoticeStatus } from '../../../../contexts/types';

const NOTICE_ID = 'woocommerce-marketplace-refresh-subscriptions';

export function RefreshButton() {
	const { refreshSubscriptions } = useContext( SubscriptionsContext );
	const [ isLoading, setIsLoading ] = useState( false );

	const refresh = () => {
		if ( isLoading ) {
			return;
		}

		removeNotice( NOTICE_ID );
		setIsLoading( true );

		refreshSubscriptions()
			.then( () => {
				addNotice(
					NOTICE_ID,
					__( 'Subscriptions refreshed.', 'woocommerce' ),
					NoticeStatus.Success
				);
			} )
			.catch( ( error ) => {
				addNotice(
					NOTICE_ID,
					sprintf(
						// translators: %s is the error message.
						__(
							'Error refreshing subscriptions: %s',
							'woocommerce'
						),
						error.message
					),
					NoticeStatus.Error,
					{
						actions: [
							{
								label: __( 'Try again', 'woocommerce' ),
								onClick: refresh,
							},
						],
					}
				);
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	};

	return (
		<Button
			className="woocommerce-marketplace__refresh-subscriptions"
			onClick={ refresh }
			isBusy={ isLoading }
		>
			<img
				src={ RefreshIcon }
				alt={ __( 'Refresh subscriptions', 'woocommerce' ) }
				className="woocommerce-marketplace__refresh-subscriptions-icon"
			/>
			{ __( 'Refresh', 'woocommerce' ) }
		</Button>
	);
}

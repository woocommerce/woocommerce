/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { default as ConnectForm } from '~/dashboard/components/connect';

type ConnectProps = {
	jetpackAuth: object;
	onConnect: () => void;
};

export const Connect: React.FC< ConnectProps > = ( {
	jetpackAuth,
	onConnect,
} ) => {
	return (
		<ConnectForm
			jetpackAuth={ jetpackAuth }
			onConnect={ () => {
				recordEvent( 'tasklist_shipping_recommendation_connect_store', {
					connect: true,
				} );
				onConnect?.();
			} }
		/>
	);
};

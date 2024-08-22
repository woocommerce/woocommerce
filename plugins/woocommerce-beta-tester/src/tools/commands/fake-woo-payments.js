/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../data/constants';

export const FAKE_WOO_PAYMENTS_ACTION_NAME = 'fakeWooPayments';

export const FakeWooPayments = () => {
	const isEnabled = useSelect( ( select ) =>
		select( STORE_KEY ).getIsFakeWooPaymentsEnabled()
	);
	const getDescription = () => {
		switch ( isEnabled ) {
			case 'yes':
				return 'Enabled 🟢';
			case 'no':
				return 'Disabled 🔴';
			case 'error':
				return 'Error 🙁';
			default:
				return 'Loading ...';
		}
	};

	return <div>{ getDescription() }</div>;
};

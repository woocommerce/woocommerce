/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store } from '../data';

export const FAKE_WOO_PAYMENTS_ACTION_NAME = 'fakeWooPayments';

export const FakeWooPayments = () => {
	const isEnabled = useSelect( ( select ) =>
		select( store ).getIsFakeWooPaymentsEnabled()
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

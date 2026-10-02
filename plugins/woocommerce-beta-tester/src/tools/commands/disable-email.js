/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store } from '../data';

export const DisableEmail = () => {
	const { isEmailDisabled } = useSelect((select) => {
		const { getIsEmailDisabled } = select(store);
		return {
			isEmailDisabled: getIsEmailDisabled(),
		};
	});

	const getEmailStatus = () => {
		switch (isEmailDisabled) {
			case 'yes':
				return 'WooCommerce emails are turned off 🔴';
			case 'no':
				return 'WooCommerce emails are turned on 🟢';
			case 'error':
				return 'Error 🙁';
			default:
				return 'Loading ...';
		}
	};

	return <div className="disable-wc-email">{getEmailStatus()}</div>;
};

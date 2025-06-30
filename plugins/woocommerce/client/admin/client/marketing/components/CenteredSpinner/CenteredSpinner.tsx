/**
 * External dependencies
 */
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './CenteredSpinner.scss';

export const CenteredSpinner = () => {
	return (
		<div className="woocommerce-centered-spinner">
			<Spinner />
		</div>
	);
};

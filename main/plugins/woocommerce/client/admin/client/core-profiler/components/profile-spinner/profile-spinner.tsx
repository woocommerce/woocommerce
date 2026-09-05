/**
 * External dependencies
 */
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './profile-spinner.scss';

export const ProfileSpinner = () => {
	return (
		<>
			<div
				className={ `woocommerce-profile-wizard__spinner` }
				data-testid="core-profiler-loading-screen"
			>
				<Spinner />
			</div>
		</>
	);
};

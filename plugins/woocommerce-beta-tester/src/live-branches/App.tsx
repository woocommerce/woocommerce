/**
 * External dependencies
 */
import { Spinner } from '@woocommerce/components';
import {
	// @ts-ignore
	__experimentalHeading as Heading,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useLiveBranchesData } from './hooks/live-branches';
import { BranchList } from './components/BranchList';

export const App = () => {
	const { branches, isLoading, isError } = useLiveBranchesData();

	return (
		<>
			<Heading level={ 1 }>
				Live Branches - Install and test WooCommerce PRs
			</Heading>
			{ isError && <p>Something Went Wrong!</p> }
			{ isLoading && <Spinner /> }
			{ ! isError && ! isLoading && <BranchList branches={ branches } /> }
		</>
	);
};

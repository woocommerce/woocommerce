/**
 * External dependencies
 */
import {
	// @ts-ignore
	__experimentalHeading as Heading,
} from '@wordpress/components';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { useLiveBranchesData } from './hooks/live-branches';
import { BranchList } from './components/BranchList';

export const App = () => {
	const { branches, isLoading } = useLiveBranchesData();

	return (
		<>
			<Heading level={ 1 }>
				Live Branches - Install and test WooCommerce PRs
			</Heading>
			{ isLoading ? <Spinner /> : <BranchList branches={ branches } /> }
		</>
	);
};

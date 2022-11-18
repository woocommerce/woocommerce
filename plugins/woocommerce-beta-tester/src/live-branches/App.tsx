/**
 * External dependencies
 */
import {
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	// @ts-ignore
	__experimentalHeading as Heading,
} from '@wordpress/components';
import { Spinner } from '@woocommerce/components';
import { css } from '@emotion/react';

/**
 * Internal dependencies
 */
import { useLiveBranchesData } from './hooks/live-branches';
import { BranchList } from './components/BranchList';

const cardStyle = css( {
	marginTop: '32px',
} );

export const App = () => {
	const { branches, isLoading } = useLiveBranchesData();

	return (
		<>
			<Heading level={ 1 }>
				Live Branches - Install and test WooCommerce PRs
			</Heading>
			<Card elevation={ 3 } css={ cardStyle }>
				<CardHeader>
					<h2>Active PRs</h2>
				</CardHeader>
				<CardBody>
					{ isLoading ? (
						<Spinner />
					) : (
						<BranchList branches={ branches } />
					) }
				</CardBody>
				<CardFooter></CardFooter>
			</Card>
		</>
	);
};

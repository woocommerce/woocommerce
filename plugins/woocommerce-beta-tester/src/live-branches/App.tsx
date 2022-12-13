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
	maxWidth: '960px',
} );

export const App = () => {
	const { branches, isLoading } = useLiveBranchesData();

	return (
		<>
			<Card elevation={ 3 } css={ cardStyle }>
				<CardHeader>
					<h2>Install and test WooCommerce Branches</h2>
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

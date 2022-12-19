/**
 * External dependencies
 */
import {
	// @ts-ignore
	__experimentalItemGroup as ItemGroup,
	// @ts-ignore
	__experimentalItem as Item,
	Button,
	Spinner,
} from '@wordpress/components';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { Branch, useLiveBranchInstall } from '../hooks/live-branches';

const BranchListItem = ( { branch }: { branch: Branch } ) => {
	const { isError, isInProgress, installAndActivate, activate, status } =
		useLiveBranchInstall(
			branch.download_url,
			`https://github.com/woocommerce/woocommerce/pull/${ branch.pr }`,
			branch.version,
			branch.install_status
		);

	const ActionButton = {
		'not-installed': () => (
			<Button variant="primary" onClick={ installAndActivate }>
				Install and Activate
			</Button>
		),
		installed: () => (
			<Button variant="primary" onClick={ activate }>
				Activate
			</Button>
		),
		active: () => (
			<Button variant="secondary" disabled>
				Activated
			</Button>
		),
	}[ status ];

	return (
		<Item>
			<p>
				Download URL:{ ' ' }
				<a href={ branch.download_url }>{ branch.download_url }</a>
			</p>
			<p>
				Pull Request:{ ' ' }
				<a
					href={ `https://github.com/woocommerce/woocommerce/pull/${ branch.pr }` }
				>
					{ branch.branch }
				</a>
			</p>
			{ isError && <p>Something Went Wrong!</p> }
			{ isInProgress && <Spinner /> }
			{ ! isError && ! isInProgress && <ActionButton /> }
		</Item>
	);
};

export const BranchList = ( { branches }: { branches: Branch[] } ) => {
	const activeBranch = branches.find(
		( branch ) => branch.install_status === 'active'
	);

	const nonActiveBranches = branches.filter(
		( branch ) => branch.install_status !== 'active'
	);

	return (
		<ItemGroup isSeparated>
			{ /* Sort the active branch if it exists to the top of the list */ }
			{ activeBranch && <BranchListItem branch={ activeBranch } /> }
			{ nonActiveBranches.map( ( branch ) => (
				<BranchListItem key={ branch.commit } branch={ branch } />
			) ) }
		</ItemGroup>
	);
};

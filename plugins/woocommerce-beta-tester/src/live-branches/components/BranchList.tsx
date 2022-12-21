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
	Card,
	CardHeader,
	CardBody,
	CardFooter,
} from '@wordpress/components';
import { useState } from 'react';
import { css } from '@emotion/react';

/**
 * Internal dependencies
 */
import { Branch, useLiveBranchInstall } from '../hooks/live-branches';

const cardStyle = css( {
	marginTop: '32px',
} );

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

const CurrentlyRunningBranch = ( { branch }: { branch: Branch } ) => {
	return (
		<>
			<h3>Currently Running:</h3>
		</>
	);
};

const BranchInfo = ( { branch }: { branch: Branch } ) => {
	return (
		<p>
			<strong>Pull Request Branch:</strong>{ ' ' }
			<a
				href={ `https://github.com/woocommerce/woocommerce/pull/${ branch.pr }` }
			>
				{ branch.branch }
			</a>
			{ ' | ' }
			<strong>Version:</strong> { branch.version } |{ ' ' }
			<strong>Download URL:</strong>{ ' ' }
			<a href={ branch.download_url }>{ branch.download_url }</a>
		</p>
	);
};

const WooCommerceVersionInfo = () => {
	// @ts-ignore
	const version = window?.wc?.WC_VERSION || 'unknown';

	return (
		<p>
			Live branch not installed. Running WooCommerce version: { version }
		</p>
	);
};

export const BranchList = ( { branches }: { branches: Branch[] } ) => {
	const [ selectedBranchCommit, setSelectedBranchCommit ] =
		useState< string >( branches.length ? branches[ 0 ].commit : '' );

	const selectedBranch = branches.filter(
		( branch: Branch ) => branch.commit === selectedBranchCommit
	)[ 0 ];

	const installedBranches = branches.filter(
		( branch ) => branch.install_status === 'installed'
	);

	const activeBranch = branches.find(
		( branch ) => branch.install_status === 'active'
	);

	// const activeBranch = branches.find(
	// 	( branch ) => branch.install_status === 'active'
	// );

	// const nonActiveBranches = branches.filter(
	// 	( branch ) => branch.install_status !== 'active'
	// );

	return (
		<>
			<Card elevation={ 2 } css={ cardStyle }>
				<CardHeader>
					<h2>Currently Running</h2>
				</CardHeader>
				<CardBody>
					{ activeBranch && <BranchInfo branch={ activeBranch } /> }
					{ ! activeBranch && <WooCommerceVersionInfo /> }
				</CardBody>
				<CardFooter></CardFooter>
			</Card>

			{ installedBranches.length && (
				<Card elevation={ 2 } css={ cardStyle }>
					<CardHeader>
						<h2>Other Installed Branches</h2>
					</CardHeader>
					<CardBody>
						<ItemGroup>
							{ installedBranches.map( ( branch ) => (
								<BranchListItem branch={ branch } />
							) ) }
						</ItemGroup>
					</CardBody>
					<CardFooter></CardFooter>
				</Card>
			) }
		</>
	);
};

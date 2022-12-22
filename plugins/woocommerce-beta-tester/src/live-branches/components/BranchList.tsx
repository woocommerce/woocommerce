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
	ComboboxControl,
} from '@wordpress/components';
import { useEffect, useState } from 'react';
import { css } from '@emotion/react';

/**
 * Internal dependencies
 */
import { Branch, useLiveBranchInstall } from '../hooks/live-branches';

const cardStyle = css( {
	marginTop: '32px',
} );

const BranchListItem = ( {
	branch,
	onBranchActive,
}: {
	branch: Branch;
	onBranchActive: ( branch: Branch ) => void;
} ) => {
	const { isError, isInProgress, installAndActivate, activate, status } =
		useLiveBranchInstall(
			branch.download_url,
			`https://github.com/woocommerce/woocommerce/pull/${ branch.pr }`,
			branch.version,
			branch.install_status
		);

	const activateBranch = async () => {
		await activate();
		onBranchActive( branch );
	};

	const installAndActivateBranch = async () => {
		await installAndActivate();
		onBranchActive( branch );
	};

	const ActionButton = {
		'not-installed': () => (
			<Button variant="primary" onClick={ installAndActivateBranch }>
				Install and Activate
			</Button>
		),
		installed: () => (
			<Button variant="primary" onClick={ activateBranch }>
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
	const [ activeBranch, setActiveBranch ] = useState< Branch | null >(
		branches.find( ( branch ) => branch.install_status === 'active' ) ||
			null
	);

	const [ selectedBranch, setSelectedBranch ] = useState( branches[ 0 ] );

	const installedBranches = branches.filter(
		( branch ) => branch.install_status === 'installed'
	);

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

			<Card elevation={ 2 } css={ cardStyle }>
				<CardHeader>
					<h2>Install and Activate Live Branches</h2>
				</CardHeader>
				<CardBody>
					<ComboboxControl
						onChange={ ( branchCommit ) => {
							if ( branchCommit ) {
								const branch = branches.find(
									( branch ) => branch.commit === branchCommit
								);

								if ( branch ) {
									setSelectedBranch( branch );
								}
							}
						} }
						value={ selectedBranch.commit }
						options={ branches.map( ( branch ) => {
							return {
								value: branch.commit,
								label: branch.branch,
							};
						} ) }
					/>

					<BranchListItem
						branch={ selectedBranch }
						onBranchActive={ setActiveBranch }
						key={ selectedBranch.commit }
					/>
				</CardBody>
				<CardFooter></CardFooter>
			</Card>
		</>
	);
};

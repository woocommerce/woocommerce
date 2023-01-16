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
import { useState } from 'react';
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
	const version = window?.wc?.wcSettings?.WC_VERSION || 'unknown';

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

	const installedBranches = branches.filter(
		( branch ) => branch.install_status === 'installed'
	);

	const uninstalledBranches = branches.filter(
		( branch ) => branch.install_status === 'not-installed'
	);

	const [ selectedBranch, setSelectedBranch ] = useState(
		uninstalledBranches[ 0 ]
	);

	const installedBranchesExist = !! installedBranches.length;

	return (
		<>
			<Card elevation={ 3 } css={ cardStyle }>
				<CardHeader>
					<h2>Currently Running</h2>
				</CardHeader>
				<CardBody>
					{ activeBranch && (
						<BranchInfo
							branch={ activeBranch }
							key={ activeBranch.version }
						/>
					) }
					{ ! activeBranch && <WooCommerceVersionInfo /> }
				</CardBody>
				<CardFooter></CardFooter>
			</Card>
			<Card elevation={ 3 } css={ cardStyle }>
				<CardHeader>
					<h2>Install and Activate Live Branches</h2>
				</CardHeader>
				<CardBody>
					<ComboboxControl
						onChange={ ( branchVersion ) => {
							if ( branchVersion ) {
								const branch = branches.find(
									( branch ) =>
										branch.version === branchVersion
								);

								if ( branch ) {
									setSelectedBranch( branch );
								}
							}
						} }
						value={ selectedBranch.version }
						options={ uninstalledBranches.map( ( branch ) => {
							return {
								value: branch.version,
								label: branch.branch,
							};
						} ) }
					/>

					<BranchListItem
						branch={ selectedBranch }
						onBranchActive={ setActiveBranch }
						key={ selectedBranch.version }
					/>
				</CardBody>
				<CardFooter></CardFooter>
			</Card>
			{ installedBranchesExist && (
				<Card elevation={ 3 } css={ cardStyle }>
					<CardHeader>
						<h2>Other Installed Branches</h2>
					</CardHeader>
					<CardBody>
						<ItemGroup>
							{ installedBranches.map( ( branch ) => (
								<BranchListItem
									branch={ branch }
									onBranchActive={ setActiveBranch }
									key={ branch.version }
								/>
							) ) }
						</ItemGroup>
					</CardBody>
					<CardFooter></CardFooter>
				</Card>
			) }
		</>
	);
};

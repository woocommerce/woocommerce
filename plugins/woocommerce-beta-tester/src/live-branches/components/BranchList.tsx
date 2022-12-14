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
	const { isError, isInstalling, install } = useLiveBranchInstall(
		branch.download_url,
		`https://github.com/woocommerce/woocommerce/pull/${ branch.pr }`
	);

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
			{ isInstalling && <Spinner /> }
			{ ! isError && ! isInstalling && (
				<Button variant="primary" onClick={ install }>
					Install
				</Button>
			) }
		</Item>
	);
};

export const BranchList = ( { branches }: { branches: Branch[] } ) => {
	return (
		<ItemGroup isSeparated>
			{ /* @ts-ignore */ }
			{ branches.map( ( branch ) => (
				<BranchListItem key={ branch.commit } branch={ branch } />
			) ) }
		</ItemGroup>
	);
};

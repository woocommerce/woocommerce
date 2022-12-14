/**
 * External dependencies
 */
import {
	Button,
	ComboboxControl,
} from '@wordpress/components';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { Branch } from '../hooks/live-branches';

const BranchInfo = ( { branch }: { branch: Branch } ) => {
	return (
		<div>
			<p>
				Pull Request Branch:{ ' ' }
				<a
					href={ `https://github.com/woocommerce/woocommerce/pull/${ branch.pr }` }
				>
					{ branch.branch }
				</a>
			</p>
			<p>
				Download URL:{ ' ' }
				<a href={ branch.download_url }>{ branch.download_url }</a>
			</p>
			<Button
				variant="primary"
				onClick={ () => console.log( 'Do install stuffs' ) }
			>
				Install
			</Button>
		</div>
	);
};

export const BranchList = ( { branches }: { branches: Branch[] } ) => {
	const [ selectedBranchCommit, setSelectedBranchCommit ] = useState< string >( branches[0].commit );
	const selectedBranch = branches.filter( ( branch: Branch ) => branch.commit === selectedBranchCommit )[0];

	return (
		<>
			Branch:{ ' ' }
			<ComboboxControl
				onChange={ ( branch ) => {
					if ( branch ) {
						setSelectedBranchCommit( branch );
					}
				} }
				value={ selectedBranchCommit }
				options={ branches.map( branch => {
					return {
						value: branch.commit,
						label: branch.branch,
					};
				} ) }
			/>
			{ selectedBranch && BranchInfo( { branch: selectedBranch } ) }
		</>
	);
};

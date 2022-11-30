/**
 * External dependencies
 */
import {
	// @ts-ignore
	__experimentalItemGroup as ItemGroup,
	// @ts-ignore
	__experimentalItem as Item,
	Button,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Branch } from '../hooks/live-branches';

const BranchListItem = ( { branch }: { branch: Branch } ) => {
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
			<Button
				variant="primary"
				onClick={ () => console.log( 'Do install stuffs' ) }
			>
				Install
			</Button>
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

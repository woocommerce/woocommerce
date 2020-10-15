/**
 * External dependencies
 */
import { SearchListControl } from '@woocommerce/components';
import { withState } from '@wordpress/compose';

const SearchListControlExample = withState( {
	selected: [],
	loading: true,
} )( ( { selected, loading, setState } ) => {
	const list = [
		{ id: 1, name: 'Apricots' },
		{ id: 2, name: 'Clementine' },
		{ id: 3, name: 'Elderberry' },
		{ id: 4, name: 'Guava' },
		{ id: 5, name: 'Lychee' },
		{ id: 6, name: 'Mulberry' },
	];

	return (
		<div>
			<button onClick={ () => setState( { loading: ! loading } ) }>
				Toggle loading state
			</button>
			<SearchListControl
				list={ list }
				isLoading={ loading }
				selected={ selected }
				onChange={ ( items ) => setState( { selected: items } ) }
			/>
		</div>
	);
} );

export const Basic = () => <SearchListControlExample />;

export default {
	title: 'WooCommerce Admin/components/SearchListControl',
	component: SearchListControl,
};

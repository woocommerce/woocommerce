/**
 * External dependencies
 */
import { boolean } from '@storybook/addon-knobs';
import { SearchListControl } from '@woocommerce/components';
import { withState } from '@wordpress/compose';
import { createElement } from '@wordpress/element';

const SearchListControlExample = withState( {
	selected: [],
	loading: false,
} )( ( { selected, loading, setState } ) => {
	const showCount = boolean( 'Show count', false );
	const isCompact = boolean( 'Compact', false );
	const isSingle = boolean( 'Single', false );

	let list = [
		{ id: 1, name: 'Apricots' },
		{ id: 2, name: 'Clementine' },
		{ id: 3, name: 'Elderberry' },
		{ id: 4, name: 'Guava' },
		{ id: 5, name: 'Lychee' },
		{ id: 6, name: 'Mulberry' },
	];
	const counts = [ 3, 1, 1, 5, 2, 0 ];

	if ( showCount ) {
		list = list.map( ( item, i ) => ( { ...item, count: counts[ i ] } ) );
	}

	return (
		<div>
			<button onClick={ () => setState( { loading: ! loading } ) }>
				Toggle loading state
			</button>
			<SearchListControl
				list={ list }
				isCompact={ isCompact }
				isLoading={ loading }
				selected={ selected }
				onChange={ ( items ) => setState( { selected: items } ) }
				isSingle={ isSingle }
			/>
		</div>
	);
} );

export const Basic = () => <SearchListControlExample />;

export default {
	title: 'WooCommerce Admin/components/SearchListControl',
	component: SearchListControl,
};

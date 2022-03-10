/**
 * External dependencies
 */
import { boolean } from '@storybook/addon-knobs';
import { SearchListControl } from '@woocommerce/components';
import { useState } from '@wordpress/element';

const SearchListControlExample = () => {
	const [ selected, setSelected ] = useState( [] );
	const [ loading, setLoading ] = useState( false );

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
			<button onClick={ () => setLoading( ! loading ) }>
				Toggle loading state
			</button>
			<SearchListControl
				list={ list }
				isCompact={ isCompact }
				isLoading={ loading }
				selected={ selected }
				onChange={ ( items ) => setSelected( items ) }
				isSingle={ isSingle }
			/>
		</div>
	);
};

export const Basic = () => <SearchListControlExample />;

export default {
	title: 'WooCommerce Admin/components/SearchListControl',
	component: SearchListControl,
};

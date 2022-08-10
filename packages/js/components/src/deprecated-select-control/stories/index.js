/**
 * External dependencies
 */
import { DeprecatedSelectControl } from '@woocommerce/components';
import { useState } from '@wordpress/element';

const options = [
	{
		key: 'apple',
		label: 'Apple',
		value: { id: 'apple' },
	},
	{
		key: 'apricot',
		label: 'Apricot',
		value: { id: 'apricot' },
	},
	{
		key: 'banana',
		label: 'Banana',
		keywords: [ 'best', 'fruit' ],
		value: { id: 'banana' },
	},
	{
		key: 'blueberry',
		label: 'Blueberry',
		value: { id: 'blueberry' },
	},
	{
		key: 'cherry',
		label: 'Cherry',
		value: { id: 'cherry' },
	},
	{
		key: 'cantaloupe',
		label: 'Cantaloupe',
		value: { id: 'cantaloupe' },
	},
	{
		key: 'dragonfruit',
		label: 'Dragon Fruit',
		value: { id: 'dragonfruit' },
	},
	{
		key: 'elderberry',
		label: 'Elderberry',
		value: { id: 'elderberry' },
	},
];

const DeprecatedSelectControlExample = () => {
	const [ state, setState ] = useState( {
		simpleSelected: [],
		simpleMultipleSelected: [],
		singleSelected: [],
		singleSelectedShowAll: [],
		multipleSelected: [],
		inlineSelected: [],
		allOptionsIncludingSelected: options[ options.length - 1 ].key,
	} );

	const {
		simpleSelected,
		simpleMultipleSelected,
		singleSelected,
		singleSelectedShowAll,
		multipleSelected,
		inlineSelected,
		allOptionsIncludingSelected,
	} = state;

	return (
		<div>
			<DeprecatedSelectControl
				label="Simple single value"
				onChange={ ( selected ) =>
					setState( { ...state, simpleSelected: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ simpleSelected }
			/>
			<br />
			<DeprecatedSelectControl
				label="Multiple values"
				multiple
				onChange={ ( selected ) =>
					setState( { ...state, simpleMultipleSelected: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ simpleMultipleSelected }
			/>
			<br />
			<DeprecatedSelectControl
				label="Show all options with default selected"
				onChange={ ( selected ) =>
					setState( {
						...state,
						allOptionsIncludingSelected: selected,
					} )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ allOptionsIncludingSelected }
				showAllOnFocus
				isSearchable
				excludeSelectedOptions={ false }
			/>
			<br />
			<DeprecatedSelectControl
				label="Single value searchable"
				isSearchable
				onChange={ ( selected ) =>
					setState( { ...state, singleSelected: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ singleSelected }
			/>
			<br />
			<DeprecatedSelectControl
				label="Single value searchable with options on refocus"
				isSearchable
				onChange={ ( selected ) =>
					setState( { ...state, singleSelectedShowAll: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ singleSelectedShowAll }
				showAllOnFocus
			/>
			<br />
			<DeprecatedSelectControl
				label="Inline tags searchable"
				isSearchable
				multiple
				inlineTags
				onChange={ ( selected ) =>
					setState( { ...state, inlineSelected: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ inlineSelected }
			/>
			<br />
			<DeprecatedSelectControl
				hideBeforeSearch
				isSearchable
				label="Hidden options before search"
				multiple
				onChange={ ( selected ) =>
					setState( { ...state, multipleSelected: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ multipleSelected }
				showClearButton
			/>
		</div>
	);
};

export const Basic = () => <DeprecatedSelectControlExample />;

export default {
	title: 'WooCommerce Admin/components/DeprecatedSelectControl',
	component: DeprecatedSelectControl,
};

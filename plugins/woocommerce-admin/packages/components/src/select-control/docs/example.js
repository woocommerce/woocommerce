/**
 * External dependencies
 */
import { SelectControl } from '@woocommerce/components';

/**
 * External dependencies
 */
import { withState } from '@wordpress/compose';

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

export default withState( {
	simpleSelected: [],
	simpleMultipleSelected: [],
	singleSelected: [],
	singleSelectedShowAll: [],
	multipleSelected: [],
	inlineSelected: [],
} )(
	( {
		simpleSelected,
		simpleMultipleSelected,
		singleSelected,
		singleSelectedShowAll,
		multipleSelected,
		inlineSelected,
		setState,
	} ) => (
		<div>
			<SelectControl
				label="Simple single value"
				onChange={ ( selected ) =>
					setState( { simpleSelected: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ simpleSelected }
			/>
			<br />
			<SelectControl
				label="Multiple values"
				multiple
				onChange={ ( selected ) =>
					setState( { simpleMultipleSelected: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ simpleMultipleSelected }
			/>
			<br />
			<SelectControl
				label="Single value searchable"
				isSearchable
				onChange={ ( selected ) =>
					setState( { singleSelected: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ singleSelected }
			/>
			<br />
			<SelectControl
				label="Single value searchable with options on refocus"
				isSearchable
				onChange={ ( selected ) =>
					setState( { singleSelectedShowAll: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ singleSelectedShowAll }
				showAllOnFocus
			/>
			<br />
			<SelectControl
				label="Inline tags searchable"
				isSearchable
				multiple
				inlineTags
				onChange={ ( selected ) =>
					setState( { inlineSelected: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ inlineSelected }
			/>
			<br />
			<SelectControl
				hideBeforeSearch
				isSearchable
				label="Hidden options before search"
				multiple
				onChange={ ( selected ) =>
					setState( { multipleSelected: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ multipleSelected }
				showClearButton
			/>
		</div>
	)
);

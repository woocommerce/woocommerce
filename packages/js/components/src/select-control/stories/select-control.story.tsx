/**
 * External dependencies
 */
import { createElement, useState } from '@wordpress/element';
/**
 * Internal dependencies
 */
import SelectControl from '../';

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

// Create a larger list of options for virtual scrolling example
const manyOptions = Array.from( { length: 2000 }, ( _, index ) => {
	const key = `option-${ index + 1 }`;
	return {
		key,
		label: `Option ${ index + 1 }`,
		value: { id: key },
	};
} );

const SelectControlExample = () => {
	const [ state, setState ] = useState( {
		simpleSelected: [],
		simpleMultipleSelected: [],
		singleSelected: [],
		singleSelectedShowAll: [],
		multipleSelected: [],
		inlineSelected: [],
		allOptionsIncludingSelected: options[ options.length - 1 ].key,
		virtualScrollSelected: [],
		disabledSelected: [
			{
				key: 'apple',
				label: 'Apple',
				value: { id: 'apple' },
			},
			{
				key: 'banana',
				label: 'Banana',
				value: { id: 'banana' },
			},
		],
		disabledInlineSelected: [
			{
				key: 'apple',
				label: 'Apple',
				value: { id: 'apple' },
			},
			{
				key: 'banana',
				label: 'Banana',
				value: { id: 'banana' },
			},
		],
	} );

	const {
		simpleSelected,
		simpleMultipleSelected,
		singleSelected,
		singleSelectedShowAll,
		multipleSelected,
		inlineSelected,
		allOptionsIncludingSelected,
		virtualScrollSelected,
		disabledSelected,
		disabledInlineSelected,
	} = state;

	return (
		<div>
			<SelectControl
				label="Simple single value"
				onChange={ ( selected ) =>
					setState( { ...state, simpleSelected: selected } )
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
					setState( { ...state, simpleMultipleSelected: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ simpleMultipleSelected }
			/>
			<br />
			<SelectControl
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
			<SelectControl
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
			<SelectControl
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
			<SelectControl
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
			<SelectControl
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
			<br />
			<SelectControl
				label="Virtual scrolling with many options"
				isSearchable
				onChange={ ( selected ) =>
					setState( { ...state, virtualScrollSelected: selected } )
				}
				options={ manyOptions }
				placeholder="Start typing to filter options..."
				selected={ virtualScrollSelected }
				showAllOnFocus
				virtualScroll={ true }
				virtualItemHeight={ 56 }
				virtualListHeight={ 56 * 6 }
			/>
			<br />
			<SelectControl
				label="Disabled select control"
				isSearchable
				multiple
				disabled
				onChange={ ( selected ) =>
					setState( { ...state, disabledSelected: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ disabledSelected }
				showClearButton
			/>
			<br />
			<SelectControl
				label="Disabled select control with inline tags"
				isSearchable
				multiple
				disabled
				inlineTags
				onChange={ ( selected ) =>
					setState( { ...state, disabledInlineSelected: selected } )
				}
				options={ options }
				placeholder="Start typing to filter options..."
				selected={ disabledInlineSelected }
				showClearButton
			/>
		</div>
	);
};

export const Basic = () => <SelectControlExample />;

export default {
	title: 'Components/SelectControl',
	component: SelectControl,
};

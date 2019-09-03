```jsx
import { Autocomplete } from '@woocommerce/components';

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
		keywords: ['best', 'fruit'],
		value: { id: 'banana' }
	},
	{
        key: 'blueberry',
		label: 'Blueberry',
		value: { id: 'blueberry' }
	},
	{
        key: 'cherry',
		label: 'Cherry',
		value: { id: 'cherry' }
	},
	{
        key: 'cantaloupe',
		label: 'Cantaloupe',
		value: { id: 'cantaloupe' }
	},
	{
        key: 'dragonfruit',
		label: 'Dragon Fruit',
		value: { id: 'dragonfruit' }
	},
	{
        key: 'elderberry',
		label: 'Elderberry',
		value: { id: 'elderberry' }
    },
];
const onChange = (selected) => {
	console.log( selected );
}

const MyAutocomplete = withState( {
	singleSelected: [],
	multipleSelected: [],
	inlineSelected: [],
} )( ( { singleSelected, multipleSelected, inlineSelected, setState } ) => (
	<div>
		<Autocomplete
			label='Single value'
			onChange={ ( selected ) => setState( { singleSelected: selected } ) }
			options={ options }
			placeholder='Start typing to filter options...'
			selected={ singleSelected }
		/>
		<br/>
		<Autocomplete
			label='Inline tags'
			multiple
			inlineTags
			onChange={ ( selected ) => setState( { inlineSelected: selected } ) }
			options={ options }
			placeholder='Start typing to filter options...'
			selected={ inlineSelected }
		/>
		<br/>
		<Autocomplete
			hideBeforeSearch
			label='Hidden options before search'
			multiple
			onChange={ ( selected ) => setState( { multipleSelected: selected } ) }
			options={ options }
			placeholder='Start typing to filter options...'
			selected={ multipleSelected }
			showClearButton
		/>
	</div>
) );
```

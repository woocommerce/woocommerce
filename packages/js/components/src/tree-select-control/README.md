# Tree Select Control

A search box which filters options while typing,
allowing a user to select multiple options from a filtered list.
The main advantage of Tree Select Controls is that it allows to distribute the options in nested groups. 

## Usage

```jsx
const options = [
       {
			value: 'EU',
			label: 'Europe',
			children: [
				{ value: 'ES', label: 'Spain' },
				{ value: 'FR', label: 'France', children: [] }, // defining children as [] is equivalent to don't have children
			],
		},
		{
			value: 'NA',
			label: 'North America',
			children: [
				{ value: 'US', label: 'United States', children: [
					{ value: 'TX', label: 'Texas' },
					{ value: 'NY', label: 'New York' },
				] },
				{ value: 'CA', label: 'Canada' },
			],
		}
     ];

<TreeSelectControl
	label="Select multiple options"
	onChange={ ( value ) => setState( { selectedValues: value } ) }
	options={ options }
	placeholder="Start typing to filter options..."
/>;
```

### Component Props

| Name                          | Type              | Default    | Description                                                                                                                                                     |
| ------------------------------| ------------------| ---------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `className`                   | string            | `null`     | Custom class name applied to the component                                                                                                                      |
| `disabled`                    | boolean           | `null`     | If true, disables the component                                                                                                                                 |
| `help`                        | string            | `null`     | Help text under the select input                                                                                                                                |
| `id`                          | string            | `null`     | Custom ID for the component                                                                                                                                     |
| `label`                       | string            | `null`     | A label to use for the main input                                                                                                                               |
| `maxVisibleTags`              | number            | `null`     | The maximum number of tags to show. Undefined, 0 or less than 0 evaluates to unlimited.                                                                         |
| `onChange`                    | function          | `noop`     | Function called when selected results change                                                                                                                    |
| `onDropdownVisibilityChange`  | function          | `noop`     | Callback when the visibility of the dropdown options is changed.                                                                                                |
| `options`                     | array             | `[]`       | (required) An array of objects for the options list. The option along with its key, label and value will be returned in the onChange event                      |
| `placeholder`                 | string            | `null`     | A placeholder for the search input                                                                                                                              |
| `selectAllLabel`              | string\|`false`   | `All`      | Label for "Select All options" node. False for disable the "Select All options" node.                                                                           |
| `value`                       | array             | `[]`       | An array of objects describing selected values.                                                                                                                 |

### Option props

| Name                          | Type              | Required   | Description                                                                                                                                                     |
| ------------------------------| ------------------| ---------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `children`                    | array             | no         | The children options inside the node                                                                                                                            |
| `key`                         | string            | no         | Optional unique key for differentiating between duplicated options, for example, same option in multiple groups                                                 |
| `label`                       | string            | yes        | A label for the option                                                                                                                                          |    
| `value`                       | string            | yes        | A value for the option                                                                                                                                          |

### OnChange prop 

For the component `value` we only need to set the value for the children options, no need to include the label or other props, also the groups are not allowed as `value` item. 
In the `onChange` function only returns and array with the selected children `value` on it.

So for example, if the options are like this
```jsx
const options = [
       {
			value: 'EU',
			label: 'Europe',
			children: [
				{ value: 'ES', label: 'Spain' },
				{ value: 'FR', label: 'France', children: [] }, 
			],
		},
		{
			value: 'NA',
			label: 'North America',
			children: [
				{ value: 'US', label: 'United States', children: [
					{ value: 'TX', label: 'Texas' },
					{ value: 'NY', label: 'New York' },
				] },
				{ value: 'CA', label: 'Canada' },
			],
		}
     ];
```

- If we select New York and Canada `onChange` will have `['NY','CA']`
- If we select Europe `onChange` will have `['ES','FR']`. Hence, `EU` is not a valid option itself and is just a group for all their children
- `value` prop sets the selected options, so it should have same format. Example: `['NY','CA']` to select `New York` and `Canada`

### selectAllLabel prop 

The component has an extra checkbox "All" as a root node allowing to select all the options.

You can customize the label for it by just adding the prop `selectAllLabel` 
```jsx
<TreeSelectControl
options={ options }
selectAllLabel={ __("Select all options", "my-awesome-plugin-domain") }
/>;
```

You can disable this feature and avoid any root element by setting the prop to `false` 


### maxVisibleTags prop

This prop allows to define the maximum number of tags to show in the input.
When the user selects a bigger number the input will show "Show X more", when X is the number of the offset tags hidden.
When clicking on that, the component will display all the tags and will change "Show X more" into "Show less" 

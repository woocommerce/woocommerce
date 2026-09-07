# Search

A search box which autocompletes results while typing, allowing for the user to select an existing object
(product, order, customer, etc). The `type` prop lists the objects that can be searched.

## Usage

```jsx
<Search
 type="products"
 placeholder="Search for a product"
 selected={ selected }
 onChange={ items => setState( { selected: items } ) }
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`allowFreeTextSearch` | Boolean | `false` | Render additional options in the autocompleter to allow free text entering depending on the type
`className` | String | `null` | Class name applied to parent div
`onChange` | Function | `noop` | Function called when selected results change, passed result list
`type` | One of: 'attributes', 'categories', 'countries', 'coupons', 'customerNames', 'customers', 'downloadIps', 'emails', 'orders', 'products', 'registeredCustomers', 'taxes', 'usernames', 'variableProducts', 'variations', 'custom' | `null` | (required) The object type to be used in searching
`autocompleter` | Completer | `null` | Custom [completer](https://github.com/WordPress/gutenberg/tree/trunk/packages/components/src/autocomplete#the-completer-interface) to be used in searching. Required when `type` is 'custom'
`placeholder` | String | `null` | A placeholder for the search input
`selected` | Array | `[]` | An array of objects describing selected values. If the label of the selected value is omitted, the Tag of that value will not be rendered inside the search box.
`inlineTags` | Boolean | `false` | Render tags inside input, otherwise render below input
`showClearButton` | Boolean | `false` | Render a 'Clear' button next to the input box to remove its contents
`staticResults` | Boolean | `false` | Render results list positioned statically instead of absolutely
`disabled` | Boolean | `false` | Whether the control is disabled or not

### Customer types

Four types search customers. They differ in which fields they match and whether guests are included.

Type | Fields searched | Customers offered
--- | --- | ---
`customerNames` | Name | All, including guests
`usernames` | Username | All, including guests
`emails` | Email | All, including guests
`registeredCustomers` | Name, username and email | Registered only

`registeredCustomers` excludes guests because it exists for reports that match customers through their user id, which guests don't have. Its suggestions append the username or email when that is what matched, so it's clear why a customer is listed.

`customers` is a deprecated alias of `customerNames`. It behaves identically, but its name suggests it searches the whole customer record rather than the name alone. Use `customerNames`.

### `selected` item structure

- `id`: One of type: number, string
- `label`: String

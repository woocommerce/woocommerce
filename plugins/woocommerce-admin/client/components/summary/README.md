SummaryList & SummaryNumber
===========================

A list of "summary numbers", performance indicators for a given store. `SummaryList` is a container element for a set of `SummaryNumber`s. Each `SummaryNumber` shows a value, label, and an optional change percentage.

## How to use:

```jsx
import { SummaryList, SummaryNumber } from 'components/summary';

render: function() {
  return (
    <SummaryList>
      <SummaryNumber value={ '$829.40' } label={ __( 'Gross Revenue', 'woo-dash' ) } delta={ 29 } />
      <SummaryNumber value={ '$24.00' } label={ __( 'Refunds', 'woo-dash' ) } delta={ -10 } selected />
      <SummaryNumber value={ '$49.90' } label={ __( 'Coupons', 'woo-dash' ) } />
    </SummaryList>
  );
}
```

## `SummaryList` Props

* `children` (required): A list of `<SummaryNumber />`s
* `label`: An optional label of this group, read to screen reader users. Defaults to "Performance Indicators".

## `SummaryNumber` Props

* `label` (required): A string description of this value, ex "Revenue", or "New Customers"
* `value` (required): A string or number value to display - a string is allowed so we can accept currency formatting.
* `delta`: A number to represent the percentage change since the last comparison period - positive numbers will show a green up arrow, negative numbers will show a red down arrow. If omitted, no change value will display.
* `context`: A string label for the comparison period.
* `selected`: A boolean used to show a highlight style on this number.

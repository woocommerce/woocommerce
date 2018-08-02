SummaryList & SummaryNumber
===========================

A list of "summary numbers", performance indicators for a given store. `SummaryList` is a container element for a set of `SummaryNumber`s. Each `SummaryNumber` shows a value, label, and an optional change percentage.

## How to use:

```jsx
import { SummaryList, SummaryNumber } from 'components/summary';

render: function() {
  return (
    <SummaryList>
      <SummaryNumber value={ '$829.40' } label={ __( 'Gross Revenue', 'wc-admin' ) } delta={ 29 } href="/analytics/report" />
      <SummaryNumber value={ '$24.00' } label={ __( 'Refunds', 'wc-admin' ) } delta={ -10 } href="/analytics/report" selected />
      <SummaryNumber value={ '$49.90' } label={ __( 'Coupons', 'wc-admin' ) } href="/analytics/report" />
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
* `href` (required): An internal link to the report focused on this number.
* `delta`: A number to represent the percentage change since the last comparison period - positive numbers will show a green up arrow, negative numbers will show a red down arrow. If omitted, no change value will display.
* `onToggle`: A function used to switch the given SummaryNumber to a button, and called on click.
* `prevLabel`: A string description of the previous value's timeframe, ex "Previous Year:". Defaults to "Previous Period:".
* `prevValue`: A string or number value to display - a string is allowed so we can accept currency formatting. If omitted, this section won't display.
* `selected`: A boolean used to show a highlight style on this number. Defaults to false.
* `reverseTrend`: A boolean used to indicate that a negative delta is "good", and should be styled like a positive (and vice-versa). Defaults to false.

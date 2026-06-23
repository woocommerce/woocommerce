# Timeline

This is a timeline for displaying data, such as events, in chronological order.
It accepts `items` for the timeline content and will order the data for you.

## Usage

```jsx
import Timeline from './Timeline';
import { orderByOptions, groupByOptions } from './Timeline';
import GridIcon from 'gridicons';

const items = [
  {
    date: new Date( 2019, 9, 28, 9, 0 ),
    icon: <GridIcon icon={ 'checkmark' } />,
    headline: 'A payment of $90.00 was successfully charged',
    body: [
      <p key={ '1' }>{ 'Fee: $2.91 ( 2.9% + $0.30 )' }</p>,
      <p key={ '2' }>{ 'Net deposit: $87.09' }</p>,
    ],
  },
  {
    date: new Date( 2019, 9, 28, 9, 32 ),
    icon: <GridIcon icon={ 'plus' } />,
    headline: '$94.16 was added to your October 29, 2019 deposit',
    body: [],
  },
  {
    date: new Date( 2019, 9, 27, 20, 9 ),
    icon: <GridIcon icon={ 'checkmark' } className={ 'is-success' } />,
    headline: 'A payment of $90.00 was successfully authorized',
    body: [],
  },
]

<Timeline
  items={ items }
  groupBy={ groupByOptions.DAY }
  orderBy={ orderByOptions.ASC }
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`className` | String | `''` | Additional class names that can be applied for styling purposes
`items` | Array | `[]` | An array of items to be displayed on the timeline
`orderBy` | String | `'asc'` | How the items should be ordered, either `'asc'` or `'desc'`
`groupBy` | String | `'day'` | How the items should be grouped, one of `'day'`, `'week'`, or `'month'`
`dateFormat` | String | `'F j, Y'` | PHP date format string used to format dates, see php.net/date
`clockFormat` | String | `'g:ia'` | PHP clock format string used to format times, see php.net/date
`timezone` | String | `'browser'` | Timezone mode used to group and render dates and times. Dates are localized using WordPress date settings. Use `'browser'` to use the user's browser timezone, or `'site'` to use the WordPress site timezone.

### Date formatting behavior

Timeline localizes rendered dates and times using WordPress date settings. The default `browser` timezone mode keeps dates grouped and rendered in the user's browser timezone when the browser provides a timezone. If the browser timezone is unavailable, Timeline falls back to the previous `format()` behavior instead of switching to the WordPress site timezone.

This means existing Timeline consumers may display localized month and day names after updating. Audit Timeline usage if locale-independent date strings are required. Use `@wordpress/date`'s `format()` before passing text to custom item content when a date string must ignore WordPress localization.

### `items` structure

A list of items with properties:

Name | Type | Default | Description
--- | --- | --- | ---
`date` | Date | Required | JavaScript Date object set to when this event happened
`icon` | Element | Required | The element used to represent the icon for this event
`headline` | Element | Required | The element used to represent the title of this event
`body` | Array | `[]` | Elements that contain details pertaining to this event
`hideTimestamp` | Bool | `false` | Allows the user to hide the timestamp associated with this event

Icon color can be customized by adding 1 of 3 classes to the icon element: `is-success` (green), `is-warning` (yellow), and `is-error` (red)

- If no class is provided the icon will be gray

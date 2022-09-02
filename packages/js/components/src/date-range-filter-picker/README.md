Date Range Picker
===

Select a range of dates or single dates

## Usage

```jsx
import {
	getDateParamsFromQuery,
	getCurrentDates,
	isoDateFormat,
	loadLocaleData,
} from '@woocommerce/date';

/**
 * External dependencies
 */
import { partialRight } from 'lodash';

const query = {};

// Fetch locale from store settings and load for date functions.
const localeSettings = {
	userLocale: 'fr_FR',
	weekdaysShort: [ 'dim', 'lun', 'mar', 'mer', 'jeu', 'ven', 'sam' ],
};
loadLocaleData( localeSettings );

const defaultDateRange = 'period=month&compare=previous_year';
const storeGetDateParamsFromQuery = partialRight( getDateParamsFromQuery, defaultDateRange );
const storeGetCurrentDates = partialRight( getCurrentDates, defaultDateRange );
const { period, compare, before, after } = storeGetDateParamsFromQuery( query );
const { primary: primaryDate, secondary: secondaryDate } = storeGetCurrentDates( query );
const dateQuery = {
	period,
	compare,
	before,
	after,
	primaryDate,
	secondaryDate,
};

<DateRangeFilterPicker
	key="daterange"
	onRangeSelect={ () => {} }
	dateQuery={ dateQuery }
	isoDateFormat={ isoDateFormat }
/>
```

### Props

Name    | Type     | Default | Description
------- | -------- | ------- | ---
`isDateFormat` | string | `null` | (required) ISO date format string
`onRangeSelect` | Function | `null` | Callback called when selection is made
`dateQuery` | object | `null` | (required) Date initialization object

## URL as the source of truth

The Date Range Picker reads parameters from the URL querystring and updates them by creating a link to reflect newly selected parameters, which is rendered as the "Update" button.

URL Parameter | Default | Possible Values
--- | --- | ---
`period` | `today` | `today`, `yesterday`, `week`, `last_week`, `month`, `last_month`, `quarter`, `last_quarter`, `year`, `last_year`, `custom`
`compare` | `previous_period` | `previous_period`, `previous_year`
`start` | none | start date for custom periods `2018-04-15`. [ISO 8601 format](https://en.wikipedia.org/wiki/ISO_8601)
`end` | none | end date for custom periods `2018-04-15`. [ISO 8601 format](https://en.wikipedia.org/wiki/ISO_8601)

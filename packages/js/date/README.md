# Date

A collection of utilities to display and work with date values.

## Installation

Install the module

```bash
pnpm install @woocommerce/date --save
```

_This package assumes that your code will run in an **ES2015+** environment. If you're using an environment that has limited or no support for ES2015+ such as lower versions of IE then using [core-js](https://github.com/zloirock/core-js) or [@babel/polyfill](https://babeljs.io/docs/en/next/babel-polyfill) will add support for these methods. Learn more about it in [Babel docs](https://babeljs.io/docs/en/next/caveats)._

## Usage

The `date` package makes use of the global `window.wcSettings.timeZone`.  If a timezone is set, the current and last periods will be converted from your browser's timezone to the store timezone.  If none is set, these periods will be based on your browser's timezone.

### Functions

<dl>
<dt><a href="#appendTimestamp">appendTimestamp</a> ⇒ <code>string</code></dt>
<dd><p>Adds timestamp to a string date.</p>
</dd>
<dt><a href="#getDateValue">getDateValue</a> ⇒ <code><a href="#DateValue">DateValue</a></code></dt>
<dd><p>Get a DateValue object for a period described by a period, compare value, and start/end
dates, for custom dates.</p>
</dd>
<dt><a href="#getDateParamsFromQueryMemoized">getDateParamsFromQueryMemoized</a> ⇒ <code>Object</code></dt>
<dd><p>Memoized internal logic of getDateParamsFromQuery().</p>
</dd>
<dt><a href="#getDateParamsFromQuery">getDateParamsFromQuery</a> ⇒ <code><a href="#DateParams">DateParams</a></code></dt>
<dd><p>Add default date-related parameters to a query object</p>
</dd>
<dt><a href="#getCurrentDatesMemoized">getCurrentDatesMemoized</a> ⇒ <code>Object</code></dt>
<dd><p>Memoized internal logic of getCurrentDates().</p>
</dd>
<dt><a href="#getCurrentDates">getCurrentDates</a> ⇒ <code>Object</code></dt>
<dd><p>Get Date Value Objects for a primary and secondary date range</p>
</dd>
<dt><a href="#getDateDifferenceInDays">getDateDifferenceInDays</a> ⇒ <code>number</code></dt>
<dd><p>Calculates the date difference between two dates. Used in calculating a matching date for previous period.</p>
</dd>
<dt><a href="#getPreviousDate">getPreviousDate</a> ⇒ <code>Object</code></dt>
<dd><p>Get the previous date for either the previous period of year.</p>
</dd>
</dl>
<dl>
<dt><a href="#toMoment">toMoment(format, str)</a> ⇒ <code>Object</code> | <code>null</code></dt>
<dd><p>Convert a string to Moment object</p>
</dd>
<dt><a href="#getRangeLabel">getRangeLabel(after, before)</a> ⇒ <code>string</code></dt>
<dd><p>Given two dates, derive a string representation</p>
</dd>
<dt><a href="#getStoreTimeZoneMoment">getStoreTimeZoneMoment()</a> ⇒ <code>string</code></dt>
<dd><p>Gets the current time in the store time zone if set.</p>
</dd>
<dt><a href="#getLastPeriod">getLastPeriod(period, compare)</a> ⇒ <code><a href="#DateValue">DateValue</a></code></dt>
<dd><p>Get a DateValue object for a period prior to the current period.</p>
</dd>
<dt><a href="#getCurrentPeriod">getCurrentPeriod(period, compare)</a> ⇒ <code><a href="#DateValue">DateValue</a></code></dt>
<dd><p>Get a DateValue object for a current period. The period begins on the first day of the period,
and ends on the current day.</p>
</dd>
<dt><a href="#getAllowedIntervalsForQuery">getAllowedIntervalsForQuery(query, defaultDateRange)</a> ⇒ <code>Array</code></dt>
<dd><p>Returns the allowed selectable intervals for a specific query.</p>
</dd>
<dt><a href="#getIntervalForQuery">getIntervalForQuery(query, defaultDateRange)</a> ⇒ <code>string</code></dt>
<dd><p>Returns the current interval to use.</p>
</dd>
<dt><a href="#getChartTypeForQuery">getChartTypeForQuery(query)</a> ⇒ <code>string</code></dt>
<dd><p>Returns the current chart type to use.</p>
</dd>
<dt><a href="#getDateFormatsForInterval">getDateFormatsForInterval(interval, [ticks], [option])</a> ⇒ <code>string</code></dt>
<dd><p>Returns date formats for the current interval.</p>
</dd>
<dt><a href="#getDateFormatsForIntervalD3">getDateFormatsForIntervalD3(interval, [ticks])</a> ⇒ <code>string</code></dt>
<dd><p>Returns d3 date formats for the current interval.
See <a href="https://github.com/d3/d3-time-format">https://github.com/d3/d3-time-format</a> for chart formats.</p>
</dd>
<dt><a href="#getDateFormatsForIntervalPhp">getDateFormatsForIntervalPhp(interval, [ticks])</a> ⇒ <code>string</code></dt>
<dd><p>Returns php date formats for the current interval.
See see <a href="https://www.php.net/manual/en/datetime.format.php">https://www.php.net/manual/en/datetime.format.php</a>.</p>
</dd>
<dt><a href="#loadLocaleData">loadLocaleData(config)</a></dt>
<dd><p>Gutenberg&#39;s moment instance is loaded with i18n values, which are
PHP date formats, ie &#39;LLL: &quot;F j, Y g:i a&quot;&#39;. Override those with translations
of moment style js formats.</p>
</dd>
<dt><a href="#validateDateInputForRange">validateDateInputForRange(type, value, [before], [after], format)</a> ⇒ <code>Object</code></dt>
<dd><p>Validate text input supplied for a date range.</p>
</dd>
</dl>

### Typedefs

<dl>
<dt><a href="#DateValue">DateValue</a> : <code>Object</code></dt>
<dd><p>DateValue Object</p>
</dd>
<dt><a href="#DateParams">DateParams</a> : <code>Object</code></dt>
<dd><p>DateParams Object</p>
</dd>
<dt><a href="#validatedDate">validatedDate</a> : <code>Object</code></dt>
<dd></dd>
</dl>

<a name="appendTimestamp"></a>

### appendTimestamp ⇒ <code>string</code>
Adds timestamp to a string date.

**Kind**: global constant
**Returns**: <code>string</code> - - String date with timestamp attached.

| Param | Type | Description |
| --- | --- | --- |
| date | <code>moment.Moment</code> | Date as a moment object. |
| timeOfDay | <code>string</code> | Either `start`, `now` or `end` of the day. |

<a name="getDateValue"></a>

### getDateValue ⇒ [<code>DateValue</code>](#DateValue)
Get a DateValue object for a period described by a period, compare value, and start/end
dates, for custom dates.

**Kind**: global constant
**Returns**: [<code>DateValue</code>](#DateValue) - - DateValue data about the selected period

| Param | Type | Description |
| --- | --- | --- |
| period | <code>string</code> | the chosen period |
| compare | <code>string</code> | `previous_period` or `previous_year` |
| [after] | <code>Object</code> | after date if custom period |
| [before] | <code>Object</code> | before date if custom period |

<a name="getDateParamsFromQueryMemoized"></a>

### getDateParamsFromQueryMemoized ⇒ <code>Object</code>
Memoized internal logic of getDateParamsFromQuery().

**Kind**: global constant
**Returns**: <code>Object</code> - - date parameters derived from query parameters with added defaults

| Param | Type | Description |
| --- | --- | --- |
| period | <code>string</code> | period value, ie `last_week` |
| compare | <code>string</code> | compare value, ie `previous_year` |
| after | <code>string</code> | date in iso date format, ie `2018-07-03` |
| before | <code>string</code> | date in iso date format, ie `2018-07-03` |
| defaultDateRange | <code>string</code> | the store's default date range |

<a name="getDateParamsFromQuery"></a>

### getDateParamsFromQuery ⇒ [<code>DateParams</code>](#DateParams)
Add default date-related parameters to a query object

**Kind**: global constant
**Returns**: [<code>DateParams</code>](#DateParams) - - date parameters derived from query parameters with added defaults

| Param | Type | Description |
| --- | --- | --- |
| query | <code>Object</code> | query object |
| query.period | <code>string</code> | period value, ie `last_week` |
| query.compare | <code>string</code> | compare value, ie `previous_year` |
| query.after | <code>string</code> | date in iso date format, ie `2018-07-03` |
| query.before | <code>string</code> | date in iso date format, ie `2018-07-03` |
| defaultDateRange | <code>string</code> | the store's default date range |

<a name="getCurrentDatesMemoized"></a>

### getCurrentDatesMemoized ⇒ <code>Object</code>
Memoized internal logic of getCurrentDates().

**Kind**: global constant
**Returns**: <code>Object</code> - - Primary and secondary DateValue objects

| Param | Type | Description |
| --- | --- | --- |
| period | <code>string</code> | period value, ie `last_week` |
| compare | <code>string</code> | compare value, ie `previous_year` |
| primaryStart | <code>Object</code> | primary query start DateTime, in Moment instance. |
| primaryEnd | <code>Object</code> | primary query start DateTime, in Moment instance. |
| secondaryStart | <code>Object</code> | primary query start DateTime, in Moment instance. |
| secondaryEnd | <code>Object</code> | primary query start DateTime, in Moment instance. |

<a name="getCurrentDates"></a>

### getCurrentDates ⇒ <code>Object</code>
Get Date Value Objects for a primary and secondary date range

**Kind**: global constant
**Returns**: <code>Object</code> - - Primary and secondary DateValue objects

| Param | Type | Description |
| --- | --- | --- |
| query | <code>Object</code> | query object |
| query.period | <code>string</code> | period value, ie `last_week` |
| query.compare | <code>string</code> | compare value, ie `previous_year` |
| query.after | <code>string</code> | date in iso date format, ie `2018-07-03` |
| query.before | <code>string</code> | date in iso date format, ie `2018-07-03` |
| defaultDateRange | <code>string</code> | the store's default date range |

<a name="getDateDifferenceInDays"></a>

### getDateDifferenceInDays ⇒ <code>number</code>
Calculates the date difference between two dates. Used in calculating a matching date for previous period.

**Kind**: global constant
**Returns**: <code>number</code> - - Difference in days.

| Param | Type | Description |
| --- | --- | --- |
| date | <code>string</code> | Date to compare |
| date2 | <code>string</code> | Secondary date to compare |

<a name="getPreviousDate"></a>

### getPreviousDate ⇒ <code>Object</code>
Get the previous date for either the previous period of year.

**Kind**: global constant
**Returns**: <code>Object</code> - - Calculated date

| Param | Type | Description |
| --- | --- | --- |
| date | <code>string</code> | Base date |
| date1 | <code>string</code> | primary start |
| date2 | <code>string</code> | secondary start |
| compare | <code>string</code> | `previous_period`  or `previous_year` |
| interval | <code>string</code> | interval |

<a name="toMoment"></a>

### toMoment(format, str) ⇒ <code>Object</code> \| <code>null</code>
Convert a string to Moment object

**Kind**: global function
**Returns**: <code>Object</code> \| <code>null</code> - - Moment object representing given string

| Param | Type | Description |
| --- | --- | --- |
| format | <code>string</code> | localized date string format |
| str | <code>string</code> | date string |

<a name="getRangeLabel"></a>

### getRangeLabel(after, before) ⇒ <code>string</code>
Given two dates, derive a string representation

**Kind**: global function
**Returns**: <code>string</code> - - text value for the supplied date range

| Param | Type | Description |
| --- | --- | --- |
| after | <code>Object</code> | start date |
| before | <code>Object</code> | end date |

<a name="getStoreTimeZoneMoment"></a>

### getStoreTimeZoneMoment() ⇒ <code>string</code>
Gets the current time in the store time zone if set.

**Kind**: global function
**Returns**: <code>string</code> - - Datetime string.
<a name="getLastPeriod"></a>

### getLastPeriod(period, compare) ⇒ [<code>DateValue</code>](#DateValue)
Get a DateValue object for a period prior to the current period.

**Kind**: global function
**Returns**: [<code>DateValue</code>](#DateValue) - -  DateValue data about the selected period

| Param | Type | Description |
| --- | --- | --- |
| period | <code>string</code> | the chosen period |
| compare | <code>string</code> | `previous_period` or `previous_year` |

<a name="getCurrentPeriod"></a>

### getCurrentPeriod(period, compare) ⇒ [<code>DateValue</code>](#DateValue)
Get a DateValue object for a current period. The period begins on the first day of the period,
and ends on the current day.

**Kind**: global function
**Returns**: [<code>DateValue</code>](#DateValue) - -  DateValue data about the selected period

| Param | Type | Description |
| --- | --- | --- |
| period | <code>string</code> | the chosen period |
| compare | <code>string</code> | `previous_period` or `previous_year` |

<a name="getAllowedIntervalsForQuery"></a>

### getAllowedIntervalsForQuery(query, defaultDateRange) ⇒ <code>Array</code>
Returns the allowed selectable intervals for a specific query.

**Kind**: global function
**Returns**: <code>Array</code> - Array containing allowed intervals.

| Param | Type | Description |
| --- | --- | --- |
| query | <code>Object</code> | Current query |
| defaultDateRange | <code>string</code> | the store's default date range |

<a name="getIntervalForQuery"></a>

### getIntervalForQuery(query, defaultDateRange) ⇒ <code>string</code>
Returns the current interval to use.

**Kind**: global function
**Returns**: <code>string</code> - Current interval.

| Param | Type | Description |
| --- | --- | --- |
| query | <code>Object</code> | Current query |
| defaultDateRange | <code>string</code> | the store's default date range |

<a name="getChartTypeForQuery"></a>

### getChartTypeForQuery(query) ⇒ <code>string</code>
Returns the current chart type to use.

**Kind**: global function
**Returns**: <code>string</code> - Current chart type.

| Param | Type | Description |
| --- | --- | --- |
| query | <code>Object</code> | Current query |
| query.chartType | <code>string</code> |  |

<a name="getDateFormatsForInterval"></a>

### getDateFormatsForInterval(interval, [ticks], [option]) ⇒ <code>string</code>
Returns date formats for the current interval.

**Kind**: global function
**Returns**: <code>string</code> - Current interval.

| Param | Type | Description |
| --- | --- | --- |
| interval | <code>string</code> | Interval to get date formats for. |
| [ticks] | <code>number</code> | Number of ticks the axis will have. |
| [option] | <code>Object</code> | Options |
| [option.type] | <code>string</code> | Date format type, d3 or php, defaults to d3. |

<a name="getDateFormatsForIntervalD3"></a>

### getDateFormatsForIntervalD3(interval, [ticks]) ⇒ <code>string</code>
Returns d3 date formats for the current interval.
See https://github.com/d3/d3-time-format for chart formats.

**Kind**: global function
**Returns**: <code>string</code> - Current interval.

| Param | Type | Description |
| --- | --- | --- |
| interval | <code>string</code> | Interval to get date formats for. |
| [ticks] | <code>number</code> | Number of ticks the axis will have. |

<a name="getDateFormatsForIntervalPhp"></a>

### getDateFormatsForIntervalPhp(interval, [ticks]) ⇒ <code>string</code>
Returns php date formats for the current interval.
See see https://www.php.net/manual/en/datetime.format.php.

**Kind**: global function
**Returns**: <code>string</code> - Current interval.

| Param | Type | Description |
| --- | --- | --- |
| interval | <code>string</code> | Interval to get date formats for. |
| [ticks] | <code>number</code> | Number of ticks the axis will have. |

<a name="loadLocaleData"></a>

### loadLocaleData(config)
Gutenberg's moment instance is loaded with i18n values, which are
PHP date formats, ie 'LLL: "F j, Y g:i a"'. Override those with translations
of moment style js formats.

**Kind**: global function

| Param | Type | Description |
| --- | --- | --- |
| config | <code>Object</code> | Locale config object, from store settings. |
| config.userLocale | <code>string</code> |  |
| config.weekdaysShort | <code>Array</code> |  |

<a name="validateDateInputForRange"></a>

### validateDateInputForRange(type, value, [before], [after], format) ⇒ <code>Object</code>
Validate text input supplied for a date range.

**Kind**: global function
**Returns**: <code>Object</code> - validatedDate - validated date object

| Param | Type | Description |
| --- | --- | --- |
| type | <code>string</code> | Designate beginning or end of range, eg `before` or `after`. |
| value | <code>string</code> | User input value |
| [before] | <code>Object</code> \| <code>null</code> | If already designated, the before date parameter |
| [after] | <code>Object</code> \| <code>null</code> | If already designated, the after date parameter |
| format | <code>string</code> | The expected date format in a user's locale |

<a name="DateValue"></a>

### DateValue : <code>Object</code>
DateValue Object

**Kind**: global typedef
**Properties**

| Name | Type | Description |
| --- | --- | --- |
| label | <code>string</code> | The translated value of the period. |
| range | <code>string</code> | The human readable value of a date range. |
| after | <code>moment.Moment</code> | Start of the date range. |
| before | <code>moment.Moment</code> | End of the date range. |

<a name="DateParams"></a>

### DateParams : <code>Object</code>
DateParams Object

**Kind**: global typedef

| Param | Type | Description |
| --- | --- | --- |
| after | <code>moment.Moment</code> \| <code>null</code> | If the period supplied is "custom", this is the after date |
| before | <code>moment.Moment</code> \| <code>null</code> | If the period supplied is "custom", this is the before date |

**Properties**

| Name | Type | Description |
| --- | --- | --- |
| period | <code>string</code> | period value, ie `last_week` |
| compare | <code>string</code> | compare valuer, ie previous_year |

<a name="validatedDate"></a>

### validatedDate : <code>Object</code>
**Kind**: global typedef
**Properties**

| Name | Type | Description |
| --- | --- | --- |
| date | <code>Object</code> \| <code>null</code> | A resulting Moment date object or null, if invalid |
| error | <code>string</code> | An optional error message if date is invalid |

# D3 Chart Component

A simple D3 line and bar chart component for timeseries data in React

## Expected Data Format
This component accepts timeseries `data` prop in the following format:
```
[
	{
		date: 'YYYY-mm-dd', // string
		category1: value, // number
		category2: value, // number
		...
	},
	...
]
```
For example:
```
[
	{
		date: '2018-06-25',
		category1: 1234.56,
		category2: 9876.54,
		...
	},
	...
]
```

## Sizing
The chart component will scale into the containing `div`; however, it is possible to additionally pass in a width and height, which will scale the viewport of the chart.

The component props can be passed in as (default values shown):
```
...
height={ 200 }
margin={ { bottom: 30, left: 40, right: 0, top: 20 } }
width={ 600 }
...
```

## Type
Currently, the component only accepts 2 different types, namely `line` or `bar`. A bar chart with multiple categories will display the categories in bar groups per date. The line chart will display multiple lines, one for each category.

## Axis Formats - tbc

## Tooltip - tbc

## Legend - tbc
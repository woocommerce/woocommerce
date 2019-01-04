/** @format */

/**
 * External dependencies
 */
import { select as d3Select } from 'd3-selection';

/**
 * Internal dependencies
 */
import { getColor } from './color';

const calculateTooltipXPosition = (
	elementCoords,
	chartCoords,
	tooltipSize,
	tooltipMargin,
	elementWidthRatio,
	tooltipPosition
) => {
	const xPosition =
		elementCoords.left + elementCoords.width * elementWidthRatio + tooltipMargin - chartCoords.left;

	if ( tooltipPosition === 'below' ) {
		return Math.max(
			tooltipMargin,
			Math.min(
				xPosition - tooltipSize.width / 2,
				chartCoords.width - tooltipSize.width - tooltipMargin
			)
		);
	}

	if ( xPosition + tooltipSize.width + tooltipMargin > chartCoords.width ) {
		return Math.max(
			tooltipMargin,
			elementCoords.left +
				elementCoords.width * ( 1 - elementWidthRatio ) -
				tooltipSize.width -
				tooltipMargin -
				chartCoords.left
		);
	}

	return xPosition;
};

const calculateTooltipYPosition = (
	elementCoords,
	chartCoords,
	tooltipSize,
	tooltipMargin,
	tooltipPosition
) => {
	if ( tooltipPosition === 'below' ) {
		return chartCoords.height;
	}

	const yPosition = elementCoords.top + tooltipMargin - chartCoords.top;
	if ( yPosition + tooltipSize.height + tooltipMargin > chartCoords.height ) {
		return Math.max( 0, elementCoords.top - tooltipSize.height - tooltipMargin - chartCoords.top );
	}

	return yPosition;
};

export const calculateTooltipPosition = ( element, chart, tooltipPosition, elementWidthRatio = 1 ) => {
	const elementCoords = element.getBoundingClientRect();
	const chartCoords = chart.getBoundingClientRect();
	const tooltipSize = d3Select( '.d3-chart__tooltip' )
		.node()
		.getBoundingClientRect();
	const tooltipMargin = 24;

	if ( tooltipPosition === 'below' ) {
		elementWidthRatio = 0;
	}

	return {
		x: calculateTooltipXPosition(
			elementCoords,
			chartCoords,
			tooltipSize,
			tooltipMargin,
			elementWidthRatio,
			tooltipPosition
		),
		y: calculateTooltipYPosition(
			elementCoords,
			chartCoords,
			tooltipSize,
			tooltipMargin,
			tooltipPosition
		),
	};
};

const getTooltipRowLabel = ( d, row, params ) => {
	if ( d[ row.key ].labelDate ) {
		return params.tooltipLabelFormat(
			d[ row.key ].labelDate instanceof Date
				? d[ row.key ].labelDate
				: new Date( d[ row.key ].labelDate )
		);
	}
	return row.key;
};

export const showTooltip = ( params, d, position ) => {
	const keys = params.orderedKeys.filter( row => row.visible ).map(
		row => `
				<li class="key-row">
					<div class="key-container">
						<span class="key-color" style="background-color:${ getColor( row.key, params ) }"></span>
						<span class="key-key">${ getTooltipRowLabel( d, row, params ) }</span>
					</div>
					<span class="key-value">${ params.tooltipValueFormat( d[ row.key ].value ) }</span>
				</li>
			`
	);

	const tooltipTitle = params.tooltipTitle
		? params.tooltipTitle
		: params.tooltipLabelFormat( d.date instanceof Date ? d.date : new Date( d.date ) );

	params.tooltip
		.style( 'left', position.x + 'px' )
		.style( 'top', position.y + 'px' )
		.style( 'visibility', 'visible' ).html( `
			<div>
				<h4>${ tooltipTitle }</h4>
				<ul>
				${ keys.join( '' ) }
				</ul>
			</div>
		` );
};

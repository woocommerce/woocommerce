/**
 * External dependencies
 */
import { select as d3Select } from 'd3-selection';
import moment from 'moment';

class ChartTooltip {
	constructor() {
		this.ref = null;
		this.chart = null;
		this.position = '';
		this.title = '';
		this.labelFormat = '';
		this.valueFormat = '';
		this.visibleKeys = '';
		this.getColor = null;
		this.margin = 24;
	}

	calculateXPosition( elementCoords, chartCoords, elementWidthRatio ) {
		const tooltipSize = this.ref.getBoundingClientRect();
		const d3BaseCoords = this.ref.parentNode
			.querySelector( '.d3-base' )
			.getBoundingClientRect();
		const leftMargin = Math.max( d3BaseCoords.left, chartCoords.left );

		if ( this.position === 'below' ) {
			return Math.max(
				this.margin,
				Math.min(
					elementCoords.left +
						elementCoords.width * 0.5 -
						tooltipSize.width / 2 -
						leftMargin,
					d3BaseCoords.width - tooltipSize.width - this.margin
				)
			);
		}

		const xPosition =
			elementCoords.left +
			elementCoords.width * elementWidthRatio +
			this.margin -
			leftMargin;

		if (
			xPosition + tooltipSize.width + this.margin >
			d3BaseCoords.width
		) {
			return Math.max(
				this.margin,
				elementCoords.left +
					elementCoords.width * ( 1 - elementWidthRatio ) -
					tooltipSize.width -
					this.margin -
					leftMargin
			);
		}

		return xPosition;
	}

	calculateYPosition( elementCoords, chartCoords ) {
		if ( this.position === 'below' ) {
			return chartCoords.height;
		}

		const tooltipSize = this.ref.getBoundingClientRect();
		const yPosition = elementCoords.top + this.margin - chartCoords.top;
		if (
			yPosition + tooltipSize.height + this.margin >
			chartCoords.height
		) {
			return Math.max(
				0,
				elementCoords.top -
					tooltipSize.height -
					this.margin -
					chartCoords.top
			);
		}

		return yPosition;
	}

	calculatePosition( element, elementWidthRatio = 1 ) {
		const elementCoords = element.getBoundingClientRect();
		const chartCoords = this.chart.getBoundingClientRect();

		if ( this.position === 'below' ) {
			elementWidthRatio = 0;
		}

		return {
			x: this.calculateXPosition(
				elementCoords,
				chartCoords,
				elementWidthRatio
			),
			y: this.calculateYPosition( elementCoords, chartCoords ),
		};
	}

	hide() {
		d3Select( this.chart )
			.selectAll( '.barfocus, .focus-grid' )
			.attr( 'opacity', '0' );
		d3Select( this.ref ).style( 'visibility', 'hidden' );
	}

	getTooltipRowLabel( d, row ) {
		if ( d[ row.key ].labelDate ) {
			return this.labelFormat(
				moment( d[ row.key ].labelDate ).toDate()
			);
		}
		return row.label || row.key;
	}

	show( d, triggerElement, parentNode, elementWidthRatio = 1 ) {
		if ( ! this.visibleKeys.length ) {
			return;
		}
		d3Select( parentNode )
			.select( '.focus-grid, .barfocus' )
			.attr( 'opacity', '1' );
		const position = this.calculatePosition(
			triggerElement,
			elementWidthRatio
		);

		const keys = this.visibleKeys.map(
			( row ) => `
					<li class="key-row">
						<div class="key-container">
							<span
								class="key-color"
								style="background-color: ${ this.getColor( row.key ) }">
							</span>
							<span class="key-key">${ this.getTooltipRowLabel( d, row ) }</span>
						</div>
						<span class="key-value">${ this.valueFormat( d[ row.key ].value ) }</span>
					</li>
				`
		);

		const tooltipTitle = this.title
			? this.title
			: this.labelFormat( moment( d.date ).toDate() );

		d3Select( this.ref )
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
	}
}

export default ChartTooltip;

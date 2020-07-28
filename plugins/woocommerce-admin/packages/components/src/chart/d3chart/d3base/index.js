/**
 * External dependencies
 */
import classNames from 'classnames';
import PropTypes from 'prop-types';
import { Component, createRef } from '@wordpress/element';
import { isEqual, throttle } from 'lodash';
import { select as d3Select } from 'd3-selection';

/**
 * Provides foundation to use D3 within React.
 *
 * React is responsible for determining when a chart should be updated (e.g. whenever data changes or the browser is
 * resized), while D3 is responsible for the actual rendering of the chart (which is performed via DOM operations that
 * happen outside of React's control).
 *
 * This component makes use of new lifecycle methods that come with React 16.3. Thus, while this component (i.e. the
 * container of the chart) is rendered during the 'render phase' the chart itself is only rendered during the 'commit
 * phase' (i.e. in 'componentDidMount' and 'componentDidUpdate' methods).
 */
export default class D3Base extends Component {
	constructor( props ) {
		super( props );

		this.chartRef = createRef();
	}

	componentDidMount() {
		this.drawUpdatedChart();
	}

	shouldComponentUpdate( nextProps ) {
		return (
			this.props.className !== nextProps.className ||
			! isEqual( this.props.data, nextProps.data ) ||
			! isEqual( this.props.orderedKeys, nextProps.orderedKeys ) ||
			this.props.drawChart !== nextProps.drawChart ||
			this.props.height !== nextProps.height ||
			this.props.chartType !== nextProps.chartType ||
			this.props.width !== nextProps.width
		);
	}

	componentDidUpdate() {
		this.drawUpdatedChart();
	}

	componentWillUnmount() {
		this.deleteChart();
	}

	delayedScroll() {
		const { tooltip } = this.props;
		return throttle( () => {
			// eslint-disable-next-line no-unused-expressions
			tooltip && tooltip.hide();
		}, 300 );
	}

	deleteChart() {
		d3Select( this.chartRef.current ).selectAll( 'svg' ).remove();
	}

	/**
	 * Renders the chart, or triggers a rendering by updating the list of params.
	 */
	drawUpdatedChart() {
		const { drawChart } = this.props;
		const svg = this.getContainer();
		drawChart( svg );
	}

	getContainer() {
		const { className, height, width } = this.props;

		this.deleteChart();

		const svg = d3Select( this.chartRef.current )
			.append( 'svg' )
			.attr( 'viewBox', `0 0 ${ width } ${ height }` )
			.attr( 'height', height )
			.attr( 'width', width )
			.attr( 'preserveAspectRatio', 'xMidYMid meet' );

		if ( className ) {
			svg.attr( 'class', `${ className }__viewbox` );
		}

		return svg.append( 'g' );
	}

	render() {
		const { className } = this.props;
		return (
			<div
				className={ classNames( 'd3-base', className ) }
				ref={ this.chartRef }
				onScroll={ this.delayedScroll() }
			/>
		);
	}
}

D3Base.propTypes = {
	className: PropTypes.string,
	data: PropTypes.array,
	orderedKeys: PropTypes.array, // required to detect changes in data
	tooltip: PropTypes.object,
	chartType: PropTypes.string,
};

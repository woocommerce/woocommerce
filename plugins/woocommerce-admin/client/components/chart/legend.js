/** @format */
/**
 * External dependencies
 */
import classNames from 'classnames';
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import { scaleOrdinal as d3ScaleOrdinal } from 'd3-scale';
import { interpolateViridis as d3InterpolateViridis } from 'd3-scale-chromatic';
import { range as d3Range } from 'd3-array';

/**
 * Internal dependencies
 */
import './style.scss';
import { formatCurrency } from 'lib/currency';

class Legend extends Component {
	render() {
		const { data, handleLegendHover, handleLegendToggle, legendDirection } = this.props;
		const d3Color = d3ScaleOrdinal().range( d3Range( 0, 1.1, 100 / ( data.length - 1 ) / 100 ) );
		return (
			<ul
				className={ classNames(
					'woocommerce-legend',
					`woocommerce-legend__direction-${ legendDirection }`,
					this.props.className
				) }
			>
				{ data.map( ( row, i ) => (
					<li
						className="woocommerce-legend__item"
						key={ row.key }
						id={ row.key }
						onMouseEnter={ handleLegendHover }
						onMouseLeave={ handleLegendHover }
						onBlur={ handleLegendHover }
						onFocus={ handleLegendHover }
					>
						<button onClick={ handleLegendToggle } id={ row.key }>
							<div className="woocommerce-legend__item-container" id={ row.key }>
								<span
									className={ classNames( 'woocommerce-legend__item-checkmark', {
										'woocommerce-legend__item-checkmark-checked': row.visible,
									} ) }
									id={ row.key }
									style={ { backgroundColor: d3InterpolateViridis( d3Color( i ) ) } }
								/>
								<span className="woocommerce-legend__item-title" id={ row.key }>
									{ row.key }
								</span>
								<span className="woocommerce-legend__item-total" id={ row.key }>
									{ formatCurrency( row.total ) }
								</span>
							</div>
						</button>
					</li>
				) ) }
			</ul>
		);
	}
}

Legend.propTypes = {
	className: PropTypes.string,
	data: PropTypes.array.isRequired,
	handleLegendToggle: PropTypes.func,
	handleLegendHover: PropTypes.func,
	legendDirection: PropTypes.oneOf( [ 'row', 'column' ] ),
};

Legend.defaultProps = {
	legendDirection: 'row',
};

export default Legend;

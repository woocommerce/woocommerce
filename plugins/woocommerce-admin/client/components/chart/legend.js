/** @format */
/**
 * External dependencies
 */
import classNames from 'classnames';
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import { formatCurrency } from 'lib/currency';
import { getColor } from './utils';

class Legend extends Component {
	render() {
		const {
			colorScheme,
			data,
			handleLegendHover,
			handleLegendToggle,
			legendDirection,
		} = this.props;
		const colorParams = {
			orderedKeys: data,
			colorScheme,
		};
		return (
			<ul
				className={ classNames(
					'woocommerce-legend',
					`woocommerce-legend__direction-${ legendDirection }`,
					this.props.className
				) }
			>
				{ data.map( row => (
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
									style={ { backgroundColor: getColor( row.key, colorParams ) } }
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
	colorScheme: PropTypes.func,
	data: PropTypes.array.isRequired,
	handleLegendToggle: PropTypes.func,
	handleLegendHover: PropTypes.func,
	legendDirection: PropTypes.oneOf( [ 'row', 'column' ] ),
};

Legend.defaultProps = {
	legendDirection: 'row',
};

export default Legend;

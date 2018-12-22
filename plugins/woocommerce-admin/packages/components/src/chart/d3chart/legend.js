/** @format */
/**
 * External dependencies
 */
import classNames from 'classnames';
import { Component, createRef } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { getColor, getFormatter } from './utils';

/**
 * A legend specifically designed for the WooCommerce admin charts.
 */
class D3Legend extends Component {
	constructor() {
		super();

		this.listRef = createRef();

		this.state = {
			isScrollable: false,
		};
	}

	componentDidMount() {
		this.updateListScroll();
		window.addEventListener( 'resize', this.updateListScroll );
	}

	componentWillUnmount() {
		window.removeEventListener( 'resize', this.updateListScroll );
	}

	updateListScroll() {
		const list = this.listRef.current;
		const scrolledToEnd = list.scrollHeight - list.scrollTop <= list.offsetHeight;
		this.setState( {
			isScrollable: ! scrolledToEnd,
		} );
	}

	render() {
		const {
			colorScheme,
			data,
			handleLegendHover,
			handleLegendToggle,
			interactive,
			legendDirection,
			legendValueFormat,
			totalLabel,
		} = this.props;
		const { isScrollable } = this.state;
		const colorParams = {
			orderedKeys: data,
			colorScheme,
		};
		const numberOfRowsVisible = data.filter( row => row.visible ).length;
		const showTotalLabel = legendDirection === 'column' && data.length > 5 && totalLabel;

		return (
			<div
				className={ classNames(
					'woocommerce-legend',
					`woocommerce-legend__direction-${ legendDirection }`,
					{
						'has-total': showTotalLabel,
						'is-scrollable': isScrollable,
					},
					this.props.className
				) }
			>
				<ul
					className="woocommerce-legend__list"
					ref={ this.listRef }
					onScroll={ showTotalLabel ? this.updateListScroll : null }
				>
					{ data.map( row => (
						<li
							className={ classNames( 'woocommerce-legend__item', {
								'woocommerce-legend__item-checked': row.visible,
							} ) }
							key={ row.key }
							id={ row.key }
							onMouseEnter={ handleLegendHover }
							onMouseLeave={ handleLegendHover }
							onBlur={ handleLegendHover }
							onFocus={ handleLegendHover }
						>
							<button
								onClick={ handleLegendToggle }
								id={ row.key }
								disabled={ ( row.visible && numberOfRowsVisible <= 1 ) || ! interactive }
							>
								<div className="woocommerce-legend__item-container" id={ row.key }>
									<span
										className={ classNames( 'woocommerce-legend__item-checkmark', {
											'woocommerce-legend__item-checkmark-checked': row.visible,
										} ) }
										id={ row.key }
										style={ { color: getColor( row.key, colorParams ) } }
									/>
									<span className="woocommerce-legend__item-title" id={ row.key }>
										{ row.key }
									</span>
									<span className="woocommerce-legend__item-total" id={ row.key }>
										{ getFormatter( legendValueFormat )( row.total ) }
									</span>
								</div>
							</button>
						</li>
					) ) }
				</ul>
				{ showTotalLabel && (
					<div className="woocommerce-legend__total">{ totalLabel }</div>
				) }
			</div>
		);
	}
}

D3Legend.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * A chromatic color function to be passed down to d3.
	 */
	colorScheme: PropTypes.func,
	/**
	 * An array of `orderedKeys`.
	 */
	data: PropTypes.array.isRequired,
	/**
	 * Handles `onClick` event.
	 */
	handleLegendToggle: PropTypes.func,
	/**
	 * Handles `onMouseEnter`/`onMouseLeave` events.
	 */
	handleLegendHover: PropTypes.func,
	/**
	 * Determines whether or not you can click on the legend
	 */
	interactive: PropTypes.bool,
	/**
	 * Display legend items as a `row` or `column` inside a flex-box.
	 */
	legendDirection: PropTypes.oneOf( [ 'row', 'column' ] ),
	/**
	 * A number formatting string or function to format the value displayed in the legend.
	 */
	legendValueFormat: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
	/**
	 * Label to describe the legend items. It will be displayed in the legend of
	 * comparison charts when there are many.
	 */
	totalLabel: PropTypes.string,
};

D3Legend.defaultProps = {
	interactive: true,
	legendDirection: 'row',
	legendValueFormat: ',',
};

export default D3Legend;

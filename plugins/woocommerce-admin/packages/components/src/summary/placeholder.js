/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import classnames from 'classnames';
import { range } from 'lodash';
import PropTypes from 'prop-types';
import { withViewportMatch } from '@wordpress/viewport';

/**
 * Internal dependencies
 */
import { getHasItemsClass } from './utils';

/**
 * `SummaryListPlaceholder` behaves like `SummaryList` but displays placeholder summary items instead of data.
 * This can be used while loading data.
 */
class SummaryListPlaceholder extends Component {
	render() {
		const { isDropdownBreakpoint } = this.props;
		const numberOfItems = isDropdownBreakpoint
			? 1
			: this.props.numberOfItems;

		const hasItemsClass = getHasItemsClass( numberOfItems );
		const classes = classnames( 'woocommerce-summary', {
			[ hasItemsClass ]: ! isDropdownBreakpoint,
			'is-placeholder': true,
		} );

		const rows = range( numberOfItems ).map( ( i ) => {
			return (
				<li
					className="woocommerce-summary__item-container is-placeholder"
					key={ i }
				>
					<span className="woocommerce-summary__item">
						<span className="woocommerce-summary__item-label" />
						<span className="woocommerce-summary__item-data">
							<span className="woocommerce-summary__item-value" />
							<div className="woocommerce-summary__item-delta">
								<span className="woocommerce-summary__item-delta-value" />
							</div>
						</span>
						<span className="woocommerce-summary__item-prev-label" />
						<span className="woocommerce-summary__item-prev-value" />
					</span>
				</li>
			);
		} );

		return (
			<ul className={ classes } aria-hidden="true">
				{ rows }
			</ul>
		);
	}
}

SummaryListPlaceholder.propTypes = {
	/**
	 * An integer with the number of summary items to display.
	 */
	numberOfItems: PropTypes.number.isRequired,
};

SummaryListPlaceholder.defaultProps = {
	numberOfRows: 5,
};

export default withViewportMatch( {
	isDropdownBreakpoint: '< large',
} )( SummaryListPlaceholder );

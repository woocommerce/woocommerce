/**
 * External dependencies
 */
import { createElement, Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import classnames from 'classnames';

/**
 * Spinner - An indeterminate circular progress indicator.
 */
class Spinner extends Component {
	render() {
		const { className } = this.props;
		const classes = classnames( 'woocommerce-spinner', className );
		return (
			<svg
				className={ classes }
				viewBox="0 0 100 100"
				xmlns="http://www.w3.org/2000/svg"
			>
				<circle
					className="woocommerce-spinner__circle"
					fill="none"
					strokeWidth="5"
					strokeLinecap="round"
					cx="50"
					cy="50"
					r="30"
				/>
			</svg>
		);
	}
}

Spinner.propTypes = {
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
};

export default Spinner;

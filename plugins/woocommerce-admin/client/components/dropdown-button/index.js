/** @format */
/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import PropTypes from 'prop-types';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * A button useful for a launcher of a dropdown component. The button is 100% width of its container and displays
 * single or multiple lines rendered as `<span/>` elments.
 *
 * @param { object } props Props passed to component.
 * @return { object } -
 */
const DropdownButton = props => {
	const { labels, isOpen, ...otherProps } = props;
	const buttonClasses = classnames( 'woocommerce-dropdown-button', {
		'is-open': isOpen,
		'is-multi-line': labels.length > 1,
	} );
	return (
		<Button className={ buttonClasses } aria-expanded={ isOpen } { ...otherProps }>
			<div className="woocommerce-dropdown-button__labels">
				{ labels.map( ( label, i ) => <span key={ i }>{ label }</span> ) }
			</div>
		</Button>
	);
};

DropdownButton.propTypes = {
	/**
	 * An array of elements to be rendered as the content of the button.
	 */
	labels: PropTypes.array,
	/**
	 * Boolean describing if the dropdown in open or not.
	 */
	isOpen: PropTypes.bool,
};

export default DropdownButton;

/**
 * External dependencies
 */
import { CheckboxControl as WPCheckboxControl } from 'wordpress-components';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Component used to show a checkbox control with styles.
 */
const CheckboxControl = ( { className, ...props } ) => {
	return (
		<WPCheckboxControl
			className={ classNames( 'wc-block-checkbox', className ) }
			{ ...props }
		/>
	);
};

CheckboxControl.propTypes = {
	className: PropTypes.string,
};

export default CheckboxControl;

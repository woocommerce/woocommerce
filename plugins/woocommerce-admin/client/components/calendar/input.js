/** @format */
/**
 * External dependencies
 */
import { Dashicon, Popover } from '@wordpress/components';
import classnames from 'classnames';
import { uniqueId } from 'lodash';
import PropTypes from 'prop-types';

const DateInput = ( { value, onChange, dateFormat, label, describedBy, error } ) => {
	const classes = classnames( 'woocommerce-calendar__input', {
		'is-empty': value.length === 0,
		'is-error': error,
	} );
	const id = uniqueId( '_woo-dates-input' );
	return (
		<div className={ classes }>
			<input
				type="text"
				className="woocommerce-calendar__input-text"
				value={ value }
				onChange={ onChange }
				aria-label={ label }
				id={ id }
				aria-describedby={ `${ id }-message` }
				placeholder={ dateFormat.toLowerCase() }
			/>
			{ error && (
				<Popover
					className="woocommerce-calendar__input-error"
					focusOnMount={ false }
					position="bottom center"
				>
					{ error }
				</Popover>
			) }
			<Dashicon icon="calendar" />
			<p className="screen-reader-text" id={ `${ id }-message` }>
				{ error || describedBy }
			</p>
		</div>
	);
};

DateInput.propTypes = {
	value: PropTypes.string,
	onChange: PropTypes.func.isRequired,
	dateFormat: PropTypes.string.isRequired,
	label: PropTypes.string.isRequired,
	describedBy: PropTypes.string.isRequired,
	error: PropTypes.string,
};

export default DateInput;

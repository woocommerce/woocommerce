/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { CustomSelectControl } from 'wordpress-components';

/**
 * Internal dependencies
 */
import './style.scss';

const Select = ( { className, label, onChange, options, value } ) => {
	return (
		<CustomSelectControl
			className={ classnames( 'wc-block-select', className, {
				'is-active': value,
			} ) }
			label={ label }
			onChange={ ( { selectedItem } ) => {
				onChange( selectedItem.key );
			} }
			options={ options }
			value={ value }
		/>
	);
};

Select.propTypes = {
	onChange: PropTypes.func.isRequired,
	options: PropTypes.arrayOf(
		PropTypes.shape( {
			key: PropTypes.string.isRequired,
			name: PropTypes.string.isRequired,
		} ).isRequired
	).isRequired,
	className: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.shape( {
		key: PropTypes.string.isRequired,
		name: PropTypes.string.isRequired,
	} ),
};

export default Select;

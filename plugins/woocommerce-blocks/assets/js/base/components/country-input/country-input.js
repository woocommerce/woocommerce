/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { CustomSelectControl } from 'wordpress-components';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import './style.scss';

const CountryInput = ( {
	className,
	countries,
	label,
	onChange,
	value = '',
} ) => {
	const options = Object.keys( countries ).map( ( key ) => ( {
		key,
		name: decodeEntities( countries[ key ] ),
	} ) );

	return (
		<CustomSelectControl
			className={ classnames( 'wc-block-country-input', className, {
				'is-active': value,
			} ) }
			label={ label }
			options={ options }
			onChange={ ( { selectedItem } ) => {
				onChange( selectedItem.key );
			} }
			value={ {
				key: value,
				name: decodeEntities( countries[ value ] ),
			} }
		/>
	);
};

CountryInput.propTypes = {
	countries: PropTypes.objectOf( PropTypes.string ).isRequired,
	onChange: PropTypes.func.isRequired,
	className: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
};

export default CountryInput;

/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import Select from '../select';

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
		<Select
			className={ className }
			label={ label }
			onChange={ onChange }
			options={ options }
			value={ options.find( ( option ) => option.key === value ) }
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

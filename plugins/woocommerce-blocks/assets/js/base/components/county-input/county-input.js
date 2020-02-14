/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import TextInput from '../text-input';
import Select from '../select';

const CountyInput = ( {
	className,
	counties,
	country,
	label,
	onChange,
	value = '',
} ) => {
	const countryCounties = counties[ country ];
	if ( ! countryCounties || Object.keys( countryCounties ).length === 0 ) {
		return (
			<TextInput
				className={ className }
				label={ label }
				onChange={ onChange }
				value={ value }
			/>
		);
	}

	const options = Object.keys( countryCounties ).map( ( key ) => ( {
		key,
		name: decodeEntities( countryCounties[ key ] ),
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

CountyInput.propTypes = {
	counties: PropTypes.objectOf(
		PropTypes.oneOfType( [
			PropTypes.array,
			PropTypes.objectOf( PropTypes.string ),
		] )
	).isRequired,
	onChange: PropTypes.func.isRequired,
	className: PropTypes.string,
	country: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
};

export default CountyInput;

/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { decodeEntities } from '@wordpress/html-entities';
import { useValidationContext } from '@woocommerce/base-context';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Select from '../select';
import { ValidationInputError } from '../validation';

const CountryInput = ( {
	className,
	countries,
	label,
	onChange,
	value = '',
	autoComplete = 'off',
	required = false,
} ) => {
	const options = Object.keys( countries ).map( ( key ) => ( {
		key,
		name: decodeEntities( countries[ key ] ),
	} ) );
	const { getValidationError } = useValidationContext();
	const errorMessage = getValidationError( 'country' );

	return (
		<div className={ classnames( className, 'wc-block-country-input' ) }>
			<Select
				label={ label }
				onChange={ onChange }
				options={ options }
				value={ options.find( ( option ) => option.key === value ) }
				required={ required }
				hasError={ !! errorMessage }
			/>
			{ autoComplete !== 'off' && (
				<input
					type="text"
					aria-hidden={ true }
					autoComplete={ autoComplete }
					value={ value }
					onChange={ ( event ) => {
						const textValue = event.target.value;
						const foundOption = options.find(
							( option ) => option.key === textValue
						);
						onChange( foundOption ? foundOption.key : '' );
					} }
					style={ {
						minHeight: '0',
						height: '0',
						border: '0',
						padding: '0',
						position: 'absolute',
					} }
					tabIndex={ -1 }
				/>
			) }
			<ValidationInputError errorMessage={ errorMessage } />
		</div>
	);
};

CountryInput.propTypes = {
	countries: PropTypes.objectOf( PropTypes.string ).isRequired,
	onChange: PropTypes.func.isRequired,
	className: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
	autoComplete: PropTypes.string,
};

export default CountryInput;

/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { decodeEntities } from '@wordpress/html-entities';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { ValidatedSelect } from '../select';
import './style.scss';

const CountryInput = ( {
	className,
	countries,
	id,
	label,
	onChange,
	value = '',
	autoComplete = 'off',
	required = false,
	errorId,
	errorMessage = __(
		'Please select a country.',
		'woocommerce'
	),
} ) => {
	const options = useMemo(
		() =>
			Object.keys( countries ).map( ( key ) => ( {
				key,
				name: decodeEntities( countries[ key ] ),
			} ) ),
		[ countries ]
	);

	return (
		<div
			className={ classnames(
				className,
				'wc-block-components-country-input'
			) }
		>
			<ValidatedSelect
				id={ id }
				label={ label }
				onChange={ onChange }
				options={ options }
				value={ options.find( ( option ) => option.key === value ) }
				errorId={ errorId }
				errorMessage={ errorMessage }
				required={ required }
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
		</div>
	);
};

CountryInput.propTypes = {
	countries: PropTypes.objectOf( PropTypes.string ).isRequired,
	onChange: PropTypes.func.isRequired,
	className: PropTypes.string,
	id: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
	autoComplete: PropTypes.string,
	errorId: PropTypes.string,
	errorMessage: PropTypes.string,
};

export default CountryInput;

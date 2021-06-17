/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { ValidatedSelect } from '../select';
import './style.scss';
import type { CountryInputWithCountriesProps } from './CountryInputProps';

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
		'woo-gutenberg-products-block'
	),
}: CountryInputWithCountriesProps ): JSX.Element => {
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

export default CountryInput;

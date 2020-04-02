/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { decodeEntities } from '@wordpress/html-entities';
import { useCallback, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ValidatedTextInput } from '../text-input';
import { ValidatedSelect } from '../select';

const StateInput = ( {
	className,
	id,
	states,
	country,
	label,
	onChange,
	autoComplete = 'off',
	value = '',
	required = false,
} ) => {
	const countryStates = states[ country ];
	const options = countryStates
		? Object.keys( countryStates ).map( ( key ) => ( {
				key,
				name: decodeEntities( countryStates[ key ] ),
		  } ) )
		: [];
	// @todo: remove this code block when issue https://github.com/woocommerce/woocommerce/issues/25854 is merged
	// Defaults to the first state when selecting a country with states, this is here
	// until a bug in Woo core is fixed.
	// see: https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1919
	useEffect( () => {
		if ( ! value && options.length ) {
			onChangeState( options[ 0 ].key );
		}
	}, [ country ] );
	/**
	 * Handles state selection onChange events. Finds a matching state by key or value.
	 *
	 * @param {Object} event event data.
	 */
	const onChangeState = useCallback(
		( stateValue ) => {
			if ( options.length > 0 ) {
				const foundOption = options.find(
					( option ) =>
						option.key === stateValue || option.name === stateValue
				);

				onChange( foundOption ? foundOption.key : '' );
				return;
			}
			onChange( stateValue );
		},
		[ onChange, options ]
	);

	if ( options.length > 0 ) {
		return (
			<>
				<ValidatedSelect
					className={ className }
					id={ id }
					label={ label }
					onChange={ onChangeState }
					options={ options }
					value={ options.find( ( option ) => option.key === value ) }
					errorMessage={ __(
						'Please select a state.',
						'woo-gutenberg-products-block'
					) }
					required={ required }
				/>
				{ autoComplete !== 'off' && (
					<input
						type="text"
						aria-hidden={ true }
						autoComplete={ autoComplete }
						value={ value }
						onChange={ ( event ) =>
							onChangeState( event.target.value )
						}
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
			</>
		);
	}
	return (
		<ValidatedTextInput
			className={ className }
			id={ id }
			label={ label }
			onChange={ onChangeState }
			autoComplete={ autoComplete }
			value={ value }
			required={ required }
		/>
	);
};

StateInput.propTypes = {
	states: PropTypes.objectOf(
		PropTypes.oneOfType( [
			PropTypes.array,
			PropTypes.objectOf( PropTypes.string ),
		] )
	).isRequired,
	onChange: PropTypes.func.isRequired,
	autoComplete: PropTypes.string,
	id: PropTypes.string,
	className: PropTypes.string,
	country: PropTypes.string,
	label: PropTypes.string,
	value: PropTypes.string,
};

export default StateInput;

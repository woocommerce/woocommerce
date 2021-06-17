/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { useCallback, useMemo } from '@wordpress/element';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { ValidatedTextInput } from '../text-input';
import { ValidatedSelect } from '../select';
import './style.scss';
import type { StateInputWithStatesProps } from './StateInputProps';

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
}: StateInputWithStatesProps ): JSX.Element => {
	const countryStates = states[ country ];
	const options = useMemo(
		() =>
			countryStates
				? Object.keys( countryStates ).map( ( key ) => ( {
						key,
						name: decodeEntities( countryStates[ key ] ),
				  } ) )
				: [],
		[ countryStates ]
	);

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
					className={ classnames(
						className,
						'wc-block-components-state-input'
					) }
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

export default StateInput;

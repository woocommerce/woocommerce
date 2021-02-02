/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import OptionLayout from './option-layout';

const Option = ( { checked, name, onChange, option } ) => {
	const {
		value,
		label,
		description,
		secondaryLabel,
		secondaryDescription,
	} = option;
	const onChangeValue = ( event ) => onChange( event.target.value );

	return (
		<label
			className={ classnames(
				'wc-block-components-radio-control__option',
				{
					'wc-block-components-radio-control__option-checked': checked,
				}
			) }
			htmlFor={ `${ name }-${ value }` }
		>
			<input
				id={ `${ name }-${ value }` }
				className="wc-block-components-radio-control__input"
				type="radio"
				name={ name }
				value={ value }
				onChange={ onChangeValue }
				checked={ checked }
				aria-describedby={ classnames( {
					[ `${ name }-${ value }__label` ]: label,
					[ `${ name }-${ value }__secondary-label` ]: secondaryLabel,
					[ `${ name }-${ value }__description` ]: description,
					[ `${ name }-${ value }__secondary-description` ]: secondaryDescription,
				} ) }
			/>
			<OptionLayout
				id={ `${ name }-${ value }` }
				label={ label }
				secondaryLabel={ secondaryLabel }
				description={ description }
				secondaryDescription={ secondaryDescription }
			/>
		</label>
	);
};

export default Option;

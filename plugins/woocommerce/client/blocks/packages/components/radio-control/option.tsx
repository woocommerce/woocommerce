/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import OptionLayout from './option-layout';
import type { RadioControlOptionProps } from './types';

const Option = ( {
	checked,
	name,
	onChange,
	option,
	disabled = false,
	highlightChecked = false,
	descriptionStackingDirection,
}: RadioControlOptionProps ) => {
	const {
		value,
		label,
		description,
		secondaryLabel,
		secondaryDescription,
		content,
	} = option;
	const onChangeValue = ( event: React.ChangeEvent< HTMLInputElement > ) =>
		onChange( event.target.value );

	return (
		// eslint-disable-next-line jsx-a11y/label-has-associated-control
		<label
			className={ clsx( 'wc-block-components-radio-control__option', {
				'wc-block-components-radio-control__option-checked': checked,
				'wc-block-components-radio-control__option--checked-option-highlighted':
					checked && highlightChecked,
			} ) }
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
				aria-describedby={ clsx( {
					[ `${ name }-${ value }__secondary-label` ]: secondaryLabel,
					[ `${ name }-${ value }__description` ]: description,
					[ `${ name }-${ value }__secondary-description` ]:
						secondaryDescription,
					[ `${ name }-${ value }__content` ]: content,
				} ) }
				aria-disabled={ disabled }
				onKeyDown={ ( event ) => {
					// Prevent option changing via keyboard when loading from server.
					if (
						disabled &&
						[
							'ArrowUp',
							'ArrowDown',
							'AllowLeft',
							'ArrowRight',
						].includes( event.key )
					) {
						event.preventDefault();
					}
				} }
			/>
			<OptionLayout
				id={ `${ name }-${ value }` }
				label={ label }
				secondaryLabel={ secondaryLabel }
				description={ description }
				secondaryDescription={ secondaryDescription }
				descriptionStackingDirection={ descriptionStackingDirection }
			/>
		</label>
	);
};

export default Option;

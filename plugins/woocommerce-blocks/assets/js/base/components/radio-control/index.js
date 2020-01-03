/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Label from '../label';
import './style.scss';

const RadioControl = ( {
	className,
	selected,
	id,
	onChange,
	options = [],
} ) => {
	const onChangeValue = ( event ) => onChange( event.target.value );
	return (
		options.length && (
			<div
				className={ classnames( 'wc-block-radio-control', className ) }
			>
				{ options.map(
					( {
						value,
						label,
						description,
						secondaryLabel,
						secondaryDescription,
					} ) => (
						<label
							key={ `${ id }-${ value }` }
							className="wc-block-radio-control__option"
							htmlFor={ `${ id }-${ value }` }
						>
							<input
								id={ `${ id }-${ value }` }
								className="wc-block-radio-control__input"
								type="radio"
								name={ id }
								value={ value }
								onChange={ onChangeValue }
								checked={ value === selected }
								aria-describedby={ classnames( {
									[ `${ id }-${ value }__label` ]: label,
									[ `${ id }-${ value }__secondary-label` ]: secondaryLabel,
									[ `${ id }-${ value }__description` ]: description,
									[ `${ id }-${ value }__secondary-description` ]: secondaryDescription,
								} ) }
							/>
							{ label && (
								<Label
									label={ label }
									wrapperElement="span"
									wrapperProps={ {
										className:
											'wc-block-radio-control__label',
										id: `${ id }-${ value }__label`,
									} }
								>
									{ label }
								</Label>
							) }
							{ secondaryLabel && (
								<Label
									label={ secondaryLabel }
									wrapperElement="span"
									wrapperProps={ {
										className:
											'wc-block-radio-control__secondary-label',
										id: `${ id }-${ value }__secondary-label`,
									} }
								>
									{ secondaryLabel }
								</Label>
							) }
							{ description && (
								<Label
									label={ description }
									wrapperElement="span"
									wrapperProps={ {
										className:
											'wc-block-radio-control__description',
										id: `${ id }-${ value }__description`,
									} }
								>
									{ description }
								</Label>
							) }
							{ secondaryDescription && (
								<Label
									label={ secondaryDescription }
									wrapperElement="span"
									wrapperProps={ {
										className:
											'wc-block-radio-control__secondary-description',
										id: `${ id }-${ value }__secondary-description`,
									} }
								>
									{ secondaryDescription }
								</Label>
							) }
						</label>
					)
				) }
			</div>
		)
	);
};

export default RadioControl;

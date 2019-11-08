/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classNames from 'classnames';
import Label from '@woocommerce/base-components/label';
import withComponentId from '@woocommerce/base-hocs/with-component-id';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Component used for 'Order by' selectors, which renders a label
 * and a <select> with the options provided in the props.
 */
const SortSelect = ( {
	className,
	componentId,
	defaultValue,
	label,
	onChange,
	options,
	screenReaderLabel,
	readOnly,
	value,
} ) => {
	const selectId = `wc-block-sort-select__select-${ componentId }`;

	return (
		<div className={ classNames( 'wc-block-sort-select', className ) }>
			<Label
				label={ label }
				screenReaderLabel={ screenReaderLabel }
				wrapperElement="label"
				wrapperProps={ {
					className: 'wc-block-sort-select__label',
					htmlFor: selectId,
				} }
			/>
			<select // eslint-disable-line jsx-a11y/no-onchange
				id={ selectId }
				className="wc-block-sort-select__select"
				defaultValue={ defaultValue }
				onChange={ onChange }
				readOnly={ readOnly }
				value={ value }
			>
				{ options.map( ( option ) => (
					<option key={ option.key } value={ option.key }>
						{ option.label }
					</option>
				) ) }
			</select>
		</div>
	);
};

SortSelect.propTypes = {
	defaultValue: PropTypes.string,
	label: PropTypes.string,
	onChange: PropTypes.func,
	options: PropTypes.arrayOf(
		PropTypes.shape( {
			key: PropTypes.string.isRequired,
			label: PropTypes.string.isRequired,
		} )
	),
	readOnly: PropTypes.bool,
	screenReaderLabel: PropTypes.string,
	value: PropTypes.string,
	// from withComponentId
	componentId: PropTypes.number.isRequired,
};

export default withComponentId( SortSelect );

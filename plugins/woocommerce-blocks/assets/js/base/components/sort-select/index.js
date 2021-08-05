/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classNames from 'classnames';
import Label from '@woocommerce/base-components/label';
import { withInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Component used for 'Order by' selectors, which renders a label
 * and a <select> with the options provided in the props.
 *
 * @param {Object} props Incoming props for the component.
 * @param {string} props.className CSS class used.
 * @param {string} props.instanceId Unique id for component instance.
 * @param {string} props.defaultValue Default value for the select.
 * @param {string} props.label Label for the select.
 * @param {function():any} props.onChange Function to call on the change event.
 * @param {Array} props.options Option values for the select.
 * @param {string} props.screenReaderLabel Screen reader label.
 * @param {boolean} props.readOnly Whether the select is read only or not.
 * @param {string} props.value The selected value.
 */
const SortSelect = ( {
	className,
	instanceId,
	defaultValue,
	label,
	onChange,
	options,
	screenReaderLabel,
	readOnly,
	value,
} ) => {
	const selectId = `wc-block-components-sort-select__select-${ instanceId }`;

	return (
		<div
			className={ classNames(
				'wc-block-sort-select',
				'wc-block-components-sort-select',
				className
			) }
		>
			<Label
				label={ label }
				screenReaderLabel={ screenReaderLabel }
				wrapperElement="label"
				wrapperProps={ {
					className:
						'wc-block-sort-select__label wc-block-components-sort-select__label',
					htmlFor: selectId,
				} }
			/>
			<select // eslint-disable-line jsx-a11y/no-onchange
				id={ selectId }
				className="wc-block-sort-select__select wc-block-components-sort-select__select"
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
	// from withInstanceId
	instanceId: PropTypes.number.isRequired,
};

export default withInstanceId( SortSelect );

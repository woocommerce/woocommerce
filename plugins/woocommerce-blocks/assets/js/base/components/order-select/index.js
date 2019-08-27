/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import Label from '../label';
import withComponentId from '../../hocs/with-component-id';
import './style.scss';

/**
 * Component used for 'Order by' selectors, which renders a label
 * and a <select> with the options provided in the props.
 */
const OrderSelect = ( { className, componentId, defaultValue, label, onChange, options, screenReaderLabel, readOnly, value } ) => {
	const selectId = `wc-block-order-select__select-${ componentId }`;

	return (
		<p className={ classNames( 'wc-block-order-select', className ) }>
			<Label
				label={ label }
				screenReaderLabel={ screenReaderLabel }
				wrapperElement="label"
				wrapperProps={ {
					className: 'wc-block-order-select__label',
					htmlFor: selectId,
				} }
			/>
			<select // eslint-disable-line jsx-a11y/no-onchange
				id={ selectId }
				className="wc-block-order-select__select"
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
		</p>
	);
};

OrderSelect.propTypes = {
	defaultValue: PropTypes.string,
	label: PropTypes.string,
	onChange: PropTypes.func,
	options: PropTypes.arrayOf( PropTypes.shape( {
		key: PropTypes.string.isRequired,
		label: PropTypes.string.isRequired,
	} ) ),
	readOnly: PropTypes.bool,
	screenReaderLabel: PropTypes.string,
	value: PropTypes.string,
	// from withComponentId
	componentId: PropTypes.number.isRequired,
};

export default withComponentId( OrderSelect );

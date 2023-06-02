/**
 * External dependencies
 */
import { withInstanceId } from '@woocommerce/base-hocs/with-instance-id';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Component used to show a checkbox control with styles.
 *
 * @param {Object} props Incoming props for the component.
 * @param {string} props.className CSS class used.
 * @param {string} props.label Label for component.
 * @param {string} props.id Id for component.
 * @param {string} props.instanceId Unique id for instance of component.
 * @param {function():any} props.onChange Function called when input changes.
 * @param {Object} props.rest Rest of properties spread.
 */
const CheckboxControl = ( {
	className,
	label,
	id,
	instanceId,
	onChange,
	...rest
} ) => {
	const checkboxId = id || `checkbox-control-${ instanceId }`;

	return (
		<label
			className={ classNames(
				'wc-block-components-checkbox',
				className
			) }
			htmlFor={ checkboxId }
		>
			<input
				id={ checkboxId }
				className="wc-block-components-checkbox__input"
				type="checkbox"
				onChange={ ( event ) => onChange( event.target.checked ) }
				{ ...rest }
			/>
			<svg
				className="wc-block-components-checkbox__mark"
				aria-hidden="true"
				xmlns="http://www.w3.org/2000/svg"
				viewBox="0 0 24 20"
			>
				<path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
			</svg>

			<span className="wc-block-components-checkbox__label">
				{ label }
			</span>
		</label>
	);
};

CheckboxControl.propTypes = {
	className: PropTypes.string,
	label: PropTypes.string,
	id: PropTypes.string,
	onChange: PropTypes.func,
};

export default withInstanceId( CheckboxControl );

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

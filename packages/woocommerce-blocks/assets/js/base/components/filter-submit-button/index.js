/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import Label from '@woocommerce/base-components/label';

/**
 * Internal dependencies
 */
import './style.scss';

const FilterSubmitButton = ( {
	className,
	disabled,
	// translators: Submit button text for filters.
	label = __( 'Go', 'woocommerce' ),
	onClick,
	screenReaderLabel = __( 'Apply filter', 'woocommerce' ),
} ) => {
	return (
		<button
			type="submit"
			className={ classNames(
				'wc-block-filter-submit-button',
				'wc-block-components-filter-submit-button',
				className
			) }
			disabled={ disabled }
			onClick={ onClick }
		>
			<Label label={ label } screenReaderLabel={ screenReaderLabel } />
		</button>
	);
};

FilterSubmitButton.propTypes = {
	className: PropTypes.string,
	/**
	 * Is the button disabled?
	 */
	disabled: PropTypes.bool,
	/**
	 * On click callback.
	 */
	onClick: PropTypes.func.isRequired,
	label: PropTypes.string,
	screenReaderLabel: PropTypes.string,
};

FilterSubmitButton.defaultProps = {
	disabled: false,
};

export default FilterSubmitButton;

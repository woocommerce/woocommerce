/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { Label } from '@woocommerce/blocks-components';

/**
 * Internal dependencies
 */
import './style.scss';

interface FilterSubmitButtonProps {
	className?: string;
	isLoading?: boolean;
	disabled?: boolean;
	label?: string;
	onClick: () => void;
	screenReaderLabel?: string;
}

const FilterSubmitButton = ( {
	className,
	isLoading,
	disabled,
	/* translators: Submit button text for filters. */
	label = __( 'Apply', 'woocommerce' ),
	onClick,
	screenReaderLabel = __( 'Apply filter', 'woocommerce' ),
}: FilterSubmitButtonProps ): JSX.Element => {
	return (
		<button
			type="submit"
			className={ clsx(
				'wp-block-button__link',
				'wc-block-filter-submit-button',
				'wc-block-components-filter-submit-button',
				{ 'is-loading': isLoading },
				className
			) }
			disabled={ disabled }
			onClick={ onClick }
		>
			<Label label={ label } screenReaderLabel={ screenReaderLabel } />
		</button>
	);
};

export default FilterSubmitButton;

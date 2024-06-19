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

interface FilterResetButtonProps {
	className?: string;
	label?: string;
	onClick: () => void;
	screenReaderLabel?: string;
}

const FilterResetButton = ( {
	className,
	/* translators: Reset button text for filters. */
	label = __( 'Reset', 'woocommerce' ),
	onClick,
	screenReaderLabel = __( 'Reset filter', 'woocommerce' ),
}: FilterResetButtonProps ): JSX.Element => {
	return (
		<button
			className={ clsx(
				'wc-block-components-filter-reset-button',
				className
			) }
			onClick={ onClick }
		>
			<Label label={ label } screenReaderLabel={ screenReaderLabel } />
		</button>
	);
};

export default FilterResetButton;

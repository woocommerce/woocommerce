/**
 * External dependencies
 */
import { useStyleProps } from '@woocommerce/base-hooks';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
const defaultYourCartLabel = __( 'Your cart', 'woocommerce' );

type Props = {
	label?: string;
	className?: string;
};

const Block = ( props: Props ): JSX.Element => {
	const styleProps = useStyleProps( props );

	return (
		<span
			className={ clsx( props.className, styleProps.className ) }
			style={ styleProps.style }
		>
			{ props.label || defaultYourCartLabel }
		</span>
	);
};

export default Block;

/**
 * External dependencies
 */
import { useStyleProps } from '@woocommerce/base-hooks';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { defaultYourCartLabel } from './constants';

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

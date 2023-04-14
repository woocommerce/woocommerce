/**
 * External dependencies
 */
import {
	useColorProps,
	useSpacingProps,
	useTypographyProps,
} from '@woocommerce/base-hooks';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { defaultYourCartLabel } from './constants';

type Props = {
	label?: string;
	className?: string;
};

const Block = ( props: Props ): JSX.Element => {
	const colorProps = useColorProps( props );
	const typographyProps = useTypographyProps( props );
	const spacingProps = useSpacingProps( props );

	return (
		<span
			className={ classNames(
				props.className,
				colorProps.className,
				typographyProps.className
			) }
			style={ {
				...colorProps.style,
				...typographyProps.style,
				...spacingProps.style,
			} }
		>
			{ props.label || defaultYourCartLabel }
		</span>
	);
};

export default Block;

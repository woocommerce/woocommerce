/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context';
import classNames from 'classnames';
import { _n, sprintf } from '@wordpress/i18n';
import {
	useColorProps,
	useSpacingProps,
	useTypographyProps,
} from '@woocommerce/base-hooks';

type Props = {
	className?: string;
};

const Block = ( props: Props ): JSX.Element => {
	const { cartItemsCount } = useStoreCart();
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
			{ sprintf(
				/* translators: %d is the count of items in the cart. */
				_n(
					'(%d item)',
					'(%d items)',
					cartItemsCount,
					'woo-gutenberg-products-block'
				),
				cartItemsCount
			) }
		</span>
	);
};

export default Block;

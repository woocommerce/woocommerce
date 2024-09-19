/**
 * External dependencies
 */
import { CART_URL } from '@woocommerce/block-settings';
import Button from '@woocommerce/base-components/button';
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
// import { useStyleProps } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import { getVariant } from '../utils';

const defaultCartButtonLabel = __( 'View my cart', 'woocommerce' );

type MiniCartCartButtonBlockProps = {
	cartButtonLabel?: string;
	className?: string;
	style?: string;
};

const Block = ( {
	className,
	cartButtonLabel,
	style,
}: MiniCartCartButtonBlockProps ): JSX.Element | null => {
	// const styleProps = useStyleProps( { style } );

	if ( ! CART_URL ) {
		return null;
	}

	return (
		<Button
			className={ clsx(
				className,
				// styleProps.className,
				'wc-block-mini-cart__footer-cart'
			) }
			href={ CART_URL }
			variant={ getVariant( className, 'outlined' ) }
		>
			{ cartButtonLabel || defaultCartButtonLabel }
		</Button>
	);
};

export default Block;

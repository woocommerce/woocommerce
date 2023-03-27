/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

export function usePreview( {
	productId,
	disabled,
	...props
}: Omit< Button.AnchorProps, 'variant' | 'href' > & {
	productId: number;
} ): Button.AnchorProps {
	const { permalink, isDisabled } = useSelect(
		( select ) => {
			const { getEditedEntityRecord } = select( 'core' );
			const product = getEditedEntityRecord< Product | undefined >(
				'postType',
				'product',
				productId
			);

			return {
				permalink: product?.permalink,
				isDisabled: ( product?.status as string ) === 'auto-draft',
			};
		},
		[ productId ]
	);

	let previewLink: string | undefined;
	if ( typeof permalink === 'string' ) {
		if ( permalink.includes( '?' ) ) {
			previewLink = `${ permalink }&preview=true`;
		} else {
			previewLink = `${ permalink }?preview=true`;
		}
	}

	return {
		'aria-label': __( 'Preview in new tab', 'woocommerce' ),
		children: __( 'Preview', 'woocommerce' ),
		target: '_blank',
		...props,
		'aria-disabled': disabled || isDisabled,
		href: previewLink,
		variant: 'tertiary',
	};
}

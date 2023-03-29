/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { MouseEvent } from 'react';

export function usePreview( {
	productId,
	disabled,
	onClick,
	onSaveSuccess,
	onSaveError,
	...props
}: Omit< Button.AnchorProps, 'aria-disabled' | 'variant' | 'href' > & {
	productId: number;
	onSaveSuccess?( product: Product ): void;
	onSaveError?( error: Error ): void;
} ): Button.AnchorProps {
	const anchorRef = useRef< HTMLAnchorElement >();

	const { permalink, productStatus, hasEdits } = useSelect(
		( select ) => {
			const { getEditedEntityRecord, hasEditsForEntityRecord } =
				select( 'core' );

			const product = getEditedEntityRecord< Product | undefined >(
				'postType',
				'product',
				productId
			);

			return {
				permalink: product?.permalink,
				productStatus: product?.status,
				hasEdits: hasEditsForEntityRecord< boolean >(
					'postType',
					'product',
					productId
				),
			};
		},
		[ productId ]
	);

	const { editEntityRecord, saveEditedEntityRecord } = useDispatch( 'core' );

	let previewLink: string | undefined;
	if ( typeof permalink === 'string' ) {
		if ( permalink.includes( '?' ) ) {
			previewLink = `${ permalink }&preview=true`;
		} else {
			previewLink = `${ permalink }?preview=true`;
		}
	}

	/**
	 * Overrides the default anchor behaviour when the product has unsaved changes.
	 * Before redirecting to the preview page all changes are saved and then the
	 * redirection is performed.
	 *
	 * @param event
	 */
	async function handleClick( event: MouseEvent< HTMLAnchorElement > ) {
		if ( typeof onClick === 'function' ) onClick( event );

		// Prevent an infinite recursion call due to `anchorRef.current?.click()`
		if ( ! hasEdits ) return;

		// Prevent the normal anchor behaviour
		event.preventDefault();

		try {
			// If the product status is `auto-draft` it's not possible to
			// reach the preview page, so the status is changed to `draft`
			// before redirecting.
			if ( ( productStatus as string ) === 'auto-draft' ) {
				await editEntityRecord( 'postType', 'product', productId, {
					status: 'draft',
				} );
			}

			// Persist the product changes before redirecting
			const publishedProduct = await saveEditedEntityRecord< Product >(
				'postType',
				'product',
				productId
			);

			// Redirect using the default anchor behaviour. This way the usage
			// of window.open is avoided which comes with a some edge cases.
			anchorRef.current?.click();

			if ( typeof onSaveSuccess === 'function' ) {
				onSaveSuccess( publishedProduct );
			}
		} catch ( error ) {
			if ( typeof onSaveError === 'function' ) {
				onSaveError( error as Error );
			}
		}
	}

	return {
		'aria-label': __( 'Preview in new tab', 'woocommerce' ),
		children: __( 'Preview', 'woocommerce' ),
		target: '_blank',
		...props,
		ref( element: HTMLAnchorElement ) {
			if ( typeof props.ref === 'function' ) props.ref( element );
			anchorRef.current = element;
		},
		'aria-disabled': disabled,
		// Note that the href is always passed for a11y support. So
		// the final rendered element is always an anchor.
		href: previewLink,
		variant: 'tertiary',
		onClick: handleClick,
	};
}

/**
 * External dependencies
 */
import { Product, ProductStatus } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { MouseEvent } from 'react';

export function usePreview( {
	disabled,
	onClick,
	onSaveSuccess,
	onSaveError,
	...props
}: Omit< Button.AnchorProps, 'aria-disabled' | 'variant' | 'href' > & {
	onSaveSuccess?( product: Product ): void;
	onSaveError?( error: Error ): void;
} ): Button.AnchorProps {
	const anchorRef = useRef< HTMLAnchorElement >();

	const [ productId ] = useEntityProp< number >(
		'postType',
		'product',
		'id'
	);
	const [ productStatus ] = useEntityProp< ProductStatus >(
		'postType',
		'product',
		'status'
	);
	const [ permalink ] = useEntityProp< string >(
		'postType',
		'product',
		'permalink'
	);

	const { hasEdits, isDisabled } = useSelect(
		( select ) => {
			const { hasEditsForEntityRecord, isSavingEntityRecord } =
				select( 'core' );
			const { isPostSavingLocked } = select( 'core/editor' );
			const isSavingLocked = isPostSavingLocked();
			const isSaving = isSavingEntityRecord< boolean >(
				'postType',
				'product',
				productId
			);

			return {
				isDisabled: isSavingLocked || isSaving,
				hasEdits: hasEditsForEntityRecord< boolean >(
					'postType',
					'product',
					productId
				),
			};
		},
		[ productId ]
	);

	const ariaDisabled = disabled || isDisabled;

	const { editEntityRecord, saveEditedEntityRecord } = useDispatch( 'core' );

	let previewLink: URL | undefined;
	if ( typeof permalink === 'string' ) {
		previewLink = new URL( permalink );
		previewLink.searchParams.append( 'preview', 'true' );
	}

	/**
	 * Overrides the default anchor behaviour when the product has unsaved changes.
	 * Before redirecting to the preview page all changes are saved and then the
	 * redirection is performed.
	 *
	 * @param event
	 */
	async function handleClick( event: MouseEvent< HTMLAnchorElement > ) {
		if ( ariaDisabled ) {
			return event.preventDefault();
		}

		if ( onClick ) {
			onClick( event );
		}

		// Prevent an infinite recursion call due to the
		// `anchorRef.current?.click()` call.
		if ( ! hasEdits ) {
			return;
		}

		// Prevent the default anchor behaviour.
		event.preventDefault();

		try {
			// If the product status is `auto-draft` it's not possible to
			// reach the preview page, so the status is changed to `draft`
			// before redirecting.
			if ( productStatus === 'auto-draft' ) {
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

			// Redirect using the default anchor behaviour. This way, the usage
			// of `window.open` is avoided which comes with some edge cases.
			anchorRef.current?.click();

			if ( onSaveSuccess ) {
				onSaveSuccess( publishedProduct );
			}
		} catch ( error ) {
			if ( onSaveError ) {
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
		'aria-disabled': ariaDisabled,
		// Note that the href is always passed for a11y support. So
		// the final rendered element is always an anchor.
		href: previewLink?.toString(),
		variant: 'tertiary',
		onClick: handleClick,
	};
}

/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { MouseEvent } from 'react';

/**
 * Internal dependencies
 */
import { useValidations } from '../../../../contexts/validation-context';
import { WPError } from '../../../../utils/get-product-error-message';
import { useProductURL } from '../../../../hooks/use-product-url';
import { PreviewButtonProps } from '../../preview-button';

export function usePreview( {
	productStatus,
	productType = 'product',
	disabled,
	onClick,
	onSaveSuccess,
	onSaveError,
	...props
}: PreviewButtonProps & {
	onSaveSuccess?( product: Product ): void;
	onSaveError?( error: WPError ): void;
} ): Button.AnchorProps {
	const anchorRef = useRef< HTMLAnchorElement >();

	const [ productId ] = useEntityProp< number >(
		'postType',
		productType,
		'id'
	);

	const { getProductURL } = useProductURL( productType );

	const { hasEdits, isDisabled } = useSelect(
		( select ) => {
			// @ts-expect-error There are no types for this.
			const { hasEditsForEntityRecord, isSavingEntityRecord } =
				select( 'core' );
			const isSaving = isSavingEntityRecord< boolean >(
				'postType',
				productType,
				productId
			);

			return {
				isDisabled: isSaving,
				hasEdits: hasEditsForEntityRecord< boolean >(
					'postType',
					productType,
					productId
				),
			};
		},
		[ productId ]
	);

	const { isValidating, validate } = useValidations();

	const ariaDisabled = disabled || isDisabled || isValidating;

	// @ts-expect-error There are no types for this.
	const { editEntityRecord, saveEditedEntityRecord } = useDispatch( 'core' );

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
			await validate();

			// If the product status is `auto-draft` it's not possible to
			// reach the preview page, so the status is changed to `draft`
			// before redirecting.
			if ( productStatus === 'auto-draft' ) {
				await editEntityRecord( 'postType', productType, productId, {
					status: 'draft',
				} );
			}

			// Persist the product changes before redirecting
			const publishedProduct = await saveEditedEntityRecord< Product >(
				'postType',
				productType,
				productId,
				{
					throwOnError: true,
				}
			);

			// Redirect using the default anchor behaviour. This way, the usage
			// of `window.open` is avoided which comes with some edge cases.
			anchorRef.current?.click();

			if ( onSaveSuccess ) {
				onSaveSuccess( publishedProduct );
			}
		} catch ( error ) {
			if ( onSaveError ) {
				let wpError = error as WPError;
				if ( ! wpError.code ) {
					wpError = {
						code: 'product_preview_error',
					} as WPError;
				}
				onSaveError( wpError );
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
		href: getProductURL( true ),
		variant: 'tertiary',
		onClick: handleClick,
	};
}

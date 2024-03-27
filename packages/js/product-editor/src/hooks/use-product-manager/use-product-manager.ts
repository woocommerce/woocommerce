/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { dispatch, useSelect, select as wpSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import type { Product, ProductStatus } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { useValidations } from '../../contexts/validation-context';
import type { WPError } from '../../utils/get-product-error-message';

function errorHandler( error: WPError, productStatus: ProductStatus ) {
	if ( error.code ) {
		return error;
	}

	if ( 'variations' in error && error.variations ) {
		return {
			code: 'variable_product_no_variation_prices',
			message: error.variations,
		};
	}

	const errorMessage = Object.values( error ).find(
		( value ) => value !== undefined
	) as string | undefined;

	if ( errorMessage !== undefined ) {
		return {
			code: 'product_form_field_error',
			message: errorMessage,
		};
	}

	return {
		code:
			productStatus === 'publish' || productStatus === 'future'
				? 'product_publish_error'
				: 'product_create_error',
	};
}

export function useProductManager< T = Product >( postType: string ) {
	const [ id ] = useEntityProp< number >( 'postType', postType, 'id' );
	const [ status ] = useEntityProp< ProductStatus >(
		'postType',
		postType,
		'status'
	);
	const [ isSaving, setIsSaving ] = useState( false );
	const [ isTrashing, setTrashing ] = useState( false );
	const { isValidating, validate } = useValidations< T >();
	const { isDirty } = useSelect(
		( select ) => ( {
			isDirty: select( 'core' ).hasEditsForEntityRecord(
				'postType',
				postType,
				id
			),
		} ),
		[ postType, id ]
	);

	async function save( extraProps: Partial< T > = {} ) {
		try {
			setIsSaving( true );

			await validate( extraProps );
			const { saveEntityRecord } = dispatch( 'core' );

			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			const { blocks, content, selection, ...editedProduct } = wpSelect(
				'core'
			).getEditedEntityRecord( 'postType', postType, id );

			const savedProduct = await saveEntityRecord(
				'postType',
				postType,
				{
					...editedProduct,
					...extraProps,
				},
				{
					throwOnError: true,
				}
			);

			return savedProduct as T;
		} catch ( error ) {
			throw errorHandler( error as WPError, status );
		} finally {
			setIsSaving( false );
		}
	}

	async function publish( extraProps: Partial< T > = {} ) {
		const isPublished = status === 'publish' || status === 'future';

		// The publish button click not only change the status of the product
		// but also save all the pending changes. So even if the status is
		// publish it's possible to save the product too.
		const data: Partial< T > = isPublished
			? extraProps
			: { status: 'publish', ...extraProps };

		return save( data );
	}

	async function trash( force = false ) {
		try {
			setTrashing( true );

			await validate();

			const { deleteEntityRecord, saveEditedEntityRecord } =
				dispatch( 'core' );

			await saveEditedEntityRecord< T >( 'postType', postType, id, {
				throwOnError: true,
			} );

			const deletedProduct = await deleteEntityRecord< T >(
				'postType',
				postType,
				id,
				{
					force,
					throwOnError: true,
				}
			);

			return deletedProduct as T;
		} catch ( error ) {
			throw errorHandler( error as WPError, status );
		} finally {
			setTrashing( false );
		}
	}

	return {
		isValidating,
		isDirty,
		isSaving,
		isPublishing: isSaving,
		isTrashing,
		save,
		publish,
		trash,
	};
}

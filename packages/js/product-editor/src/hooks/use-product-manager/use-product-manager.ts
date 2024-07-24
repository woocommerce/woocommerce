/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { dispatch, useSelect, select as wpSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { Product, ProductStatus, PRODUCTS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { useValidations } from '../../contexts/validation-context';
import type { WPError } from '../../hooks/use-error-handler';
import { AUTO_DRAFT_NAME } from '../../utils/constants';

export function errorHandler( error: WPError, productStatus: ProductStatus ) {
	if ( error.code ) {
		return error;
	}

	const errorObj = Object.values( error ).find(
		( value ) => value !== undefined
	) as WPError | undefined;

	if ( 'variations' in error && error.variations ) {
		return {
			...errorObj,
			code: 'variable_product_no_variation_prices',
		};
	}

	if ( errorObj !== undefined ) {
		return {
			...errorObj,
			code: 'product_form_field_error',
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
	const [ name, , prevName ] = useEntityProp< string >(
		'postType',
		postType,
		'name'
	);
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
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
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
			const { blocks, content, selection, ...editedProduct } =
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore
				wpSelect( 'core' ).getEntityRecordEdits(
					'postType',
					postType,
					id
				);

			const savedProduct = await saveEntityRecord(
				'postType',
				postType,
				{
					...editedProduct,
					...extraProps,
					id,
				},
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore
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

	async function copyToDraft() {
		try {
			// When "Copy to a new draft" is used on an unsaved product with a filled-out name,
			// the name is retained in the copied product.
			const data =
				AUTO_DRAFT_NAME === prevName && name !== prevName
					? { name }
					: {};
			setIsSaving( true );
			const duplicatedProduct = await dispatch(
				PRODUCTS_STORE_NAME
			).duplicateProduct( id, data );

			return duplicatedProduct as T;
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

			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
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
		copyToDraft,
	};
}

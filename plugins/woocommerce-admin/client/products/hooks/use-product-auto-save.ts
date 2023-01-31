/**
 * External dependencies
 */
import { debounce, pick } from 'lodash';
import { Product } from '@woocommerce/data';
import { useDebounce } from '@wordpress/compose';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { useFormContext } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { useProductHelper } from '../use-product-helper';

export const useProductAutoSave = ( dependencies: ( keyof Product )[] ) => {
	const [ isAutoSaving, setIsAutoSaving ] = useState( false );
	const { isValidForm, setValue, values } = useFormContext< Product >();
	const { createOrUpdateProductWithStatus } = useProductHelper();

	const dependencyValues = dependencies.reduce(
		( acc: unknown[], key: keyof Product ) => {
			acc.push( values[ key ] );
			return acc;
		},
		[]
	);

	const handleProductUpdated = ( updatedProduct: Product ) => {
		if ( ! updatedProduct || ! updatedProduct.id ) {
			return;
		}

		setValue( 'id', updatedProduct.id );
		setIsAutoSaving( false );
	};

	const saveProduct = () => {
		setIsAutoSaving( true );
		createOrUpdateProductWithStatus(
			values.id || null,
			pick( values, dependencies ),
			values.status || 'auto-draft',
			true
		).then( handleProductUpdated );
	};

	const debouncedSaveProduct = useDebounce( saveProduct, 250 );

	useEffect( () => {
		if ( values.id || ! isValidForm || isAutoSaving ) {
			return;
		}
		debouncedSaveProduct();
	}, dependencyValues );

	return {
		isAutoSaving,
	};
};

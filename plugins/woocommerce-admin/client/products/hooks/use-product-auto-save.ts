/**
 * External dependencies
 */
import { pick } from 'lodash';
import { Product } from '@woocommerce/data';
import { useDebounce } from '@wordpress/compose';
import { useEffect, useState } from '@wordpress/element';
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
		( dependenciesAccumulator: unknown[], key: keyof Product ) => {
			dependenciesAccumulator.push( values[ key ] );
			return dependenciesAccumulator;
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

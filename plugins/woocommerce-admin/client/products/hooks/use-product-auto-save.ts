/**
 * External dependencies
 */
import { pick } from 'lodash';
import { Product } from '@woocommerce/data';
import { useEffect, useState } from '@wordpress/element';
import { useFormContext } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { useProductHelper } from '../use-product-helper';

export const useProductAutoSave = ( dependencies: ( keyof Product )[] ) => {
	const [ isAutoSaving, setIsAutoSaving ] = useState( false );
	const { isValidForm, values, resetForm } = useFormContext< Product >();
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

		resetForm( {
			...values,
			id: updatedProduct.id,
		} );

		setIsAutoSaving( false );
	};

	useEffect( () => {
		if ( ! values.id || ! isValidForm ) {
			return;
		}
		setIsAutoSaving( true );
		createOrUpdateProductWithStatus(
			values.id || null,
			pick( values, dependencies ),
			values.status || 'auto-draft',
			true
		).then( handleProductUpdated );
	}, dependencyValues );

	return {
		isAutoSaving,
	};
};

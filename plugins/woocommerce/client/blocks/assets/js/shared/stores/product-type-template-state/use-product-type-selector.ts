/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
/**
 * Internal dependencies
 */
import { store as productTypeTemplateStateStore } from '../product-type-template-state';
import type { ProductTypeProps } from './types';

type ProductTypeSelector = {
	productTypes: ProductTypeProps[];
	current: ProductTypeProps | undefined;
	set: ( productType: string ) => void;
	registeredListeners: string[];
	registerListener: ( listener: string ) => void;
	unregisterListener: ( listener: string ) => void;
};

/**
 * Hook to get data from the store to manage the product type selector and listeners.
 * Also, it provides a function to switch the current product type.
 * It's a layer of abstraction to the store.
 *
 * @return {ProductTypeSelector} The product type selector data and functions.
 */
export default function useProductTypeSelector(): ProductTypeSelector {
	/*
	 * Get the registered listeners, available product types and the current product type
	 * from the store.
	 */
	const { productTypes, current, registeredListeners } = useSelect(
		( select ) => {
			const {
				getProductTypes,
				getCurrentProductType,
				getRegisteredListeners,
			} = select( productTypeTemplateStateStore );

			return {
				productTypes: getProductTypes(),
				current: getCurrentProductType(),
				registeredListeners: getRegisteredListeners(),
			};
		},
		[]
	);

	const { switchProductType, registerListener, unregisterListener } =
		useDispatch( productTypeTemplateStateStore );

	return {
		productTypes,
		current,
		set: switchProductType,
		registeredListeners,
		registerListener,
		unregisterListener,
	};
}

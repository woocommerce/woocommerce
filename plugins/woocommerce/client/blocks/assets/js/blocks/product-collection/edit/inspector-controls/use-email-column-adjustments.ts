/**
 * External dependencies
 */
import { useIsEmailEditor } from '@woocommerce/email-editor';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { type ProductCollectionAttributes } from '../../types';

/**
 * Custom hook to adjust columns to 1 when in email editor.
 *
 * @param {ProductCollectionAttributes} attributes    - The attributes of the product collection block.
 * @param {Function}                    setAttributes - Function to set block attributes.
 */
const useEmailColumnAdjustments = (
	attributes: ProductCollectionAttributes,
	setAttributes: (
		attributes: Partial< ProductCollectionAttributes >
	) => void
) => {
	const { displayLayout } = attributes;
	const isEmail = useIsEmailEditor();

	useEffect( () => {
		if ( ! isEmail ) {
			return;
		}

		// Only adjust columns if currently more than 1 and not already 1
		if ( displayLayout.columns && displayLayout.columns > 1 ) {
			setAttributes( {
				displayLayout: {
					...displayLayout,
					columns: 1,
				},
			} );
		}
	}, [ isEmail, displayLayout, setAttributes ] );
};

export default useEmailColumnAdjustments;

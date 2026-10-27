/**
 * External dependencies
 */
import { useIsEmailEditor } from '@woocommerce/email-editor';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { type ProductCollectionAttributes, LayoutOptions } from '../../types';
import { sanitizeDisplayLayout } from '../../utils';

const MAX_EMAIL_COLUMNS = 2;

/**
 * Custom hook to adjust columns when in email editor.
 * Limits columns to a maximum of 2 for email compatibility.
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

		// Only adjust columns if currently more than the max allowed for email.
		// Other layouts do not store a column count.
		if (
			displayLayout?.type === LayoutOptions.GRID &&
			displayLayout.columns &&
			displayLayout.columns > MAX_EMAIL_COLUMNS
		) {
			setAttributes( {
				displayLayout: sanitizeDisplayLayout( {
					...displayLayout,
					columns: MAX_EMAIL_COLUMNS,
				} ),
			} );
		}
	}, [ isEmail, displayLayout, setAttributes ] );
};

export default useEmailColumnAdjustments;

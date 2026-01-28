/**
 * External dependencies
 */
import { WCUser, useUser } from '@woocommerce/data';
import { useEntityProp, store as coreStore } from '@wordpress/core-data';
import { dispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';

export function useMetaboxHiddenProduct() {
	const [ isSaving, setIsSaving ] = useState( false );

	const { user, isRequesting } = useUser();
	const [
		metaboxhiddenProduct,
		setMetaboxhiddenProduct,
		prevMetaboxhiddenProduct,
	] = useEntityProp< string[] >(
		'root',
		'user',
		'metaboxhidden_product',
		user.id
	);

	async function saveMetaboxhiddenProduct(
		value: string[]
	): Promise< WCUser > {
		try {
			setIsSaving( true );

			const { saveEntityRecord } = dispatch( coreStore );
			const currentUser: WCUser = ( await saveEntityRecord(
				'root',
				'user',
				{
					id: user.id,
					metaboxhidden_product: value,
				}
			) ) as never;

			return currentUser;
		} finally {
			setIsSaving( false );
		}
	}

	return {
		isLoading: ( isRequesting as boolean ) || isSaving,
		metaboxhiddenProduct,
		prevMetaboxhiddenProduct,
		setMetaboxhiddenProduct,
		saveMetaboxhiddenProduct,
	};
}

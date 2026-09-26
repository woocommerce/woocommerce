/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import useRecordCompletionTime from '../use-record-completion-time';

type UseLoadSampleProductsProps = {
	redirectUrlAfterSuccess: string;
};

const useLoadSampleProducts = ( {
	redirectUrlAfterSuccess,
}: UseLoadSampleProductsProps ) => {
	const [ isRequesting, setIsRequesting ] = useState< boolean >( false );
	const { createNotice } = useDispatch( 'core/notices' );
	const { recordCompletionTime } = useRecordCompletionTime( 'products' );

	const loadSampleProduct = async () => {
		recordEvent( 'tasklist_add_product', {
			method: 'sample_product',
		} );
		recordCompletionTime();
		setIsRequesting( true );
		try {
			await apiFetch( {
				path: `${ WC_ADMIN_NAMESPACE }/onboarding/tasks/import_sample_products`,
				method: 'POST',
			} );

			if ( redirectUrlAfterSuccess ) {
				window.location.href = redirectUrlAfterSuccess;
				return;
			}
		} catch ( error: unknown ) {
			const message =
				error instanceof Error && error.message
					? error.message
					: __(
							'There was an error importing the sample products',
							'woocommerce'
					  );

			createNotice( 'error', message );
		}
		setIsRequesting( false );
	};

	return {
		loadSampleProduct,
		isLoadingSampleProducts: isRequesting,
	};
};

export default useLoadSampleProducts;

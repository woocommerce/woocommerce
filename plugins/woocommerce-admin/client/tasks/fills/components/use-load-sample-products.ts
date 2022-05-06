/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';

type UseLoadSampleProductsProps = {
	redirectUrlAfterSuccess: string;
};

const useLoadSampleProducts = ( {
	redirectUrlAfterSuccess,
}: UseLoadSampleProductsProps ) => {
	const [ isRequesting, setIsRequesting ] = useState< boolean >( false );
	const { createNotice } = useDispatch( 'core/notices' );

	const loadSampleProduct = async () => {
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

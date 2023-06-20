/**
 * External dependencies
 */
import { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

const API_NAMESPACE = 'woocommerce-docs/v1';

export const useManifests = () => {
	const [ manifests, setManifests ] = useState< string[] >( [] );
	const [ loading, setLoading ] = useState< boolean >( true );

	useEffect( () => {
		const getManifests = async () => {
			const res = await apiFetch< string[] >( {
				path: `${ API_NAMESPACE }/manifests`,
				method: 'GET',
			} );

			setManifests( res );

			setLoading( false );
		};

		getManifests();
	}, [] );

	return { manifests, isLoading: loading };
};

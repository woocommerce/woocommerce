/**
 * External dependencies
 */
import { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

const API_NAMESPACE = 'woocommerce-docs/v1';

export const useCompletedJobs = () => {
	const [ jobs, setJobs ] = useState< string[] >( [] );
	const [ loading, setLoading ] = useState< boolean >( true );

	useEffect( () => {
		const getCompletedJobs = async () => {
			const res = await apiFetch< string[] >( {
				path: `${ API_NAMESPACE }/completed_jobs`,
				method: 'GET',
			} );

			setJobs( res );
			setLoading( false );
		};

		getCompletedJobs();
	}, [] );

	return { jobs, isLoading: loading };
};

/**
 * External dependencies
 */
import { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

const API_NAMESPACE = 'woocommerce-docs/v1';

type JobLog = {
	action_id: string;
	date: string;
	message: string;
};

export const useJobLog = () => {
	const [ jobLog, setJobLog ] = useState< JobLog[] >( [] );
	const [ loading, setLoading ] = useState< boolean >( true );

	useEffect( () => {
		const getJobLog = async () => {
			const res = await apiFetch< JobLog[] >( {
				path: `${ API_NAMESPACE }/job_log`,
				method: 'GET',
			} );

			setJobLog( res );
			setLoading( false );
		};

		getJobLog();
	}, [] );

	return { jobs: jobLog, isLoading: loading };
};

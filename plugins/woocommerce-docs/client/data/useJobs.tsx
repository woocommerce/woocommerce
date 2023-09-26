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
	const [ jobLogs, setJobLogs ] = useState< JobLog[] >( [] );
	const [ loading, setLoading ] = useState< boolean >( true );
	const [ error, setError ] = useState< string | null >( null );

	useEffect( () => {
		const getJobLog = async () => {
			try {
				const res = await apiFetch< JobLog[] >( {
					path: `${ API_NAMESPACE }/job_log`,
					method: 'GET',
				} );

				setJobLogs( res );
				setLoading( false );
			} catch ( err: unknown ) {
				if (
					err &&
					typeof err === 'object' &&
					'message' in err &&
					typeof err.message === 'string'
				) {
					setError( `Error occurred: ${ err.message }` );
					setLoading( false );
				} else {
					setError( 'An unknown error occurred.' );
					setLoading( false );
				}
			}
		};

		getJobLog();
	}, [] );

	return { jobLogs, isLoading: loading, error };
};

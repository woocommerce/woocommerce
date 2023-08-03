/**
 * External dependencies
 */
import { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

const API_NAMESPACE = 'woocommerce-docs/v1';

export const useManifests = () => {
	const [ manifests, setManifests ] = useState< string[] >( [] );
	const [ loading, setLoading ] = useState< boolean >( true );
	const [ error, setError ] = useState< string | null >( null );

	useEffect( () => {
		const getManifests = async () => {
			try {
				const res = await apiFetch< string[] >( {
					path: `${ API_NAMESPACE }/manifests`,
					method: 'GET',
				} );

				setManifests( res );
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

		getManifests();
	}, [] );

	const deleteManifest = async ( manifest: string ) => {
		setLoading( true );

		try {
			const res = await apiFetch< string[] >( {
				path: `${ API_NAMESPACE }/manifests`,
				method: 'DELETE',
				data: { manifest },
			} );

			setManifests( res );
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

	const createManifest = async ( manifest: string ) => {
		setLoading( true );

		try {
			const res = await apiFetch< string[] >( {
				path: `${ API_NAMESPACE }/manifests`,
				method: 'POST',
				data: { manifest },
			} );

			setManifests( res );
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

	return {
		manifests,
		error,
		isLoading: loading,
		createManifest,
		deleteManifest,
	};
};

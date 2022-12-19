// @ts-ignore
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from 'react';
// @ts-ignore
import { API_NAMESPACE } from '../../features/data/constants';

export type Branch = {
	branch: string;
	commit: string;
	download_url: string;
	update_date: string;
	version: string;
	pr: number;
};

export const useLiveBranchesData = () => {
	const [ branches, setBranches ] = useState< Branch[] >( [] );
	const [ loading, setLoading ] = useState< boolean >( true );

	useEffect( () => {
		const getBranches = async () => {
			const res = await apiFetch( {
				path: `${ API_NAMESPACE }/live-branches/manifest/v1`,
				method: 'GET',
			} );

			setBranches(
				// @ts-ignore
				Object.entries( res.pr ).map( ( [ , value ] ) => {
					return value;
				} ) as Branch[]
			);

			setLoading( false );
		};

		getBranches();
	}, [] );

	return { branches, isLoading: loading };
};

export const useLiveBranchInstall = (
	downloadUrl: string,
	prName: string,
	version: string
) => {
	const [ isInstalling, setIsInstalling ] = useState( false );
	const [ isError, setIsError ] = useState( false );

	const install = async () => {
		setIsInstalling( true );

		try {
			const installResult = await apiFetch< Response >( {
				path: `${ API_NAMESPACE }/live-branches/install/v1`,
				method: 'POST',
				body: JSON.stringify( {
					pr_name: prName,
					download_url: downloadUrl,
					version,
				} ),
			} );

			if ( installResult.status >= 400 ) {
				throw new Error( 'Could not install' );
			}

			const deactivateResult = await apiFetch< Response >( {
				path: `${ API_NAMESPACE }/live-branches/deactivate_core/v1`,
			} );

			if ( deactivateResult.status >= 400 ) {
				throw new Error( 'Could not deactivate' );
			}

			const activateResult = await apiFetch< Response >( {
				path: `${ API_NAMESPACE }/live-branches/activate/v1`,
				method: 'POST',
				body: JSON.stringify( {
					version,
				} ),
			} );

			if ( activateResult.status >= 400 ) {
				throw new Error( 'Could not activate' );
			}
		} catch ( e ) {
			setIsError( true );
		}

		setIsInstalling( false );
	};

	return {
		install,
		isError,
		isInstalling,
	};
};

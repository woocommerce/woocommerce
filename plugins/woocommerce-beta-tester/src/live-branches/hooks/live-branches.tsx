// @ts-ignore
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from 'react';
// @ts-ignore
import { API_NAMESPACE } from '../../features/data/constants';

type PluginStatus = 'not-installed' | 'installed' | 'active';

export type Branch = {
	branch: string;
	commit: string;
	download_url: string;
	update_date: string;
	version: string;
	pr: number;
	install_status: PluginStatus;
};

type LiveBranchesResponse = {
	pr: { [ key: string ]: Branch };
	master: Branch;
};

export const useLiveBranchesData = () => {
	const [ branches, setBranches ] = useState< Branch[] >( [] );
	const [ loading, setLoading ] = useState< boolean >( true );
	const [ isError, setIsError ] = useState< boolean >( false );

	useEffect( () => {
		const getBranches = async () => {
			try {
				const res = await apiFetch< LiveBranchesResponse >( {
					path: `${ API_NAMESPACE }/live-branches/manifest/v1`,
					method: 'GET',
				} );

				const prBranches = Object.entries( res.pr ).map(
					( [ , value ] ) => {
						return value;
					}
				) as Branch[];

				setBranches( [ res.master, ...prBranches ] );
				setLoading( false );
			} catch ( e ) {
				setIsError( true );
				setLoading( false );
			}
		};

		getBranches();
	}, [] );

	return { branches, isLoading: loading, isError };
};

export const useLiveBranchInstall = (
	downloadUrl: string,
	prName: string,
	version: string,
	status: PluginStatus
) => {
	const [ isInProgress, setIsInProgress ] = useState( false );
	const [ isError, setIsError ] = useState( false );
	const [ pluginStatus, setPluginStatus ] = useState( status );

	const activate = async () => {
		setIsInProgress( true );

		try {
			const deactivateResult = await apiFetch< Response >( {
				path: `${ API_NAMESPACE }/live-branches/deactivate/v1`,
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

		setPluginStatus( 'active' );
		setIsInProgress( false );
	};

	const installAndActivate = async () => {
		setIsInProgress( true );

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

			setPluginStatus( 'installed' );

			const deactivateResult = await apiFetch< Response >( {
				path: `${ API_NAMESPACE }/live-branches/deactivate/v1`,
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

			setPluginStatus( 'active' );
		} catch ( e ) {
			setIsError( true );
		}

		setIsInProgress( false );
	};

	return {
		installAndActivate,
		activate,
		isError,
		isInProgress,
		status: pluginStatus,
	};
};

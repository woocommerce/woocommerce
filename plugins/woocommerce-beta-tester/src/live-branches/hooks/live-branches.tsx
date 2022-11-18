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

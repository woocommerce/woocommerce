// @ts-ignore
import apiFetch from '@wordpress/api-fetch';
import { useState } from 'react';
// @ts-ignore
import { API_NAMESPACE } from '../../features/data/constants';
import data from './jetpack-branches-test-data.json';

export type Branch = {
  branch: string; 
  commit: string; 
  download_url: string; 
  update_date: string; 
  version: string; 
  pr: number;
}

export const useLiveBranchesData = () => {
  const [branches, setBranches] = useState<Branch[]>([]);

  const getBranches = async () => {

    const res = await apiFetch( {
			path: `${ API_NAMESPACE }/live-branches/manifest/v1`,
			method: 'GET',
		} )

    // @ts-ignore
    setBranches(Object.entries(res).map(([, value]) => {
      return value;
    }) as Branch[]);
  }
  
  getBranches();
  
  return branches;
} 

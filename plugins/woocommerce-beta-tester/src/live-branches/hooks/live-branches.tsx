// @ts-ignore
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
  return Object.entries(data.pr).map(([, value]) => {
    return value;
  }) as Branch[];
} 

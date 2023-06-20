/**
 * Internal dependencies
 */
import { setTinyContent, getTinyContent } from '../utils/tiny-tools';

export const useTinyEditor = () => {
	return { setContent: setTinyContent, getContent: getTinyContent };
};

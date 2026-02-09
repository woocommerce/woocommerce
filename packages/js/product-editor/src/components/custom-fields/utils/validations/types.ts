/**
 * Internal dependencies
 */
import type { Metadata } from '../../../../types';

export type ValidationError = Record< keyof Metadata< string >, string >;

export type ValidationErrors = {
	[ id: string ]: ValidationError | undefined;
};

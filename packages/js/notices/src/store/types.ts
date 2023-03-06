/**
 * Internal dependencies
 */
import { createNotice } from './actions';

export type Notices = Array< ReturnType< typeof createNotice >[ 'notice' ] >;

export type State = {
	[ context: string ]: Notices;
};

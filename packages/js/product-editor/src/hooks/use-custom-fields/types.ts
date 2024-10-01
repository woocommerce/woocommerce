/**
 * Internal dependencies
 */
import { Metadata } from '../../types';

export type DisjoinMetas< T extends Metadata< string > > = {
	customFields: T[];
	otherMetas: T[];
};

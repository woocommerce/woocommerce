/**
 * Internal dependencies
 */
import { recordTracksFactory, getPostId } from '../utils';

type TracksData = Record<
	string,
	string | number | null | Array< Record< string, string | number | null > >
>;

export const recordNameTracks = recordTracksFactory< TracksData >(
	'name_completion',
	() => ( {
		post_id: getPostId(),
	} )
);

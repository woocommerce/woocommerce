/**
 * Internal dependencies
 */
import { recordTracksFactory, getPostId } from '../utils';

type TracksData = Record<
	string,
	string | number | Array< Record< string, string | number > >
>;

export const recordNameTracks = recordTracksFactory< TracksData >(
	'name_completion',
	() => ( {
		post_id: getPostId(),
	} )
);

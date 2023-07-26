/**
 * Internal dependencies
 */
import { recordTracksFactory, getPostId } from '../utils';

type TracksData = Record< string, string | number | null | Array< string > >;

export const recordCategoryTracks = recordTracksFactory< TracksData >(
	'category_completion',
	() => ( {
		post_id: getPostId(),
	} )
);

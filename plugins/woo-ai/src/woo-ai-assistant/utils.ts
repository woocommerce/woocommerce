/**
 * Internal dependencies
 */
import { recordTracksFactory, getPostId } from '../utils';

type TracksData = Record<
	string,
	string | number | null | Array< Record< string, string | number | null > >
>;

const recordWooAIAssistantTracks = recordTracksFactory< TracksData >(
	'ai_assistant',
	() => ( {
		post_id: getPostId(),
	} )
);

export default recordWooAIAssistantTracks;

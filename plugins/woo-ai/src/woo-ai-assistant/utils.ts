/**
 * Internal dependencies
 */
import { recordTracksFactory, getPostId } from '../utils';

type TracksData = Record<
	string,
	string | number | null | Array< Record< string, string | number | null > >
>;

export const recordWooAIAssistantTracks = recordTracksFactory< TracksData >(
	'ai_assistant',
	() => ( {
		post_id: getPostId(),
	} )
);

export const prepareFormData = (
	message: string,
	token: string,
	threadID?: string
) => {
	const formData = new FormData();
	formData.append( 'message', message );
	formData.append( 'token', token );
	if ( threadID && threadID.length > 0 ) {
		formData.append( 'thread_id', threadID );
	}
	return formData;
};

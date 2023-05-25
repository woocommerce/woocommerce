/**
 * External dependencies
 */
import { useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { askQuestion } from '../utils';

type UseCompletionProps = {
	onStreamMessage: ( message: string, chunk: string ) => void;
	onCompletionFinished?: ( reason: string, previousContent: string ) => void;
	onStreamError?: ( event: Event ) => void;
};

export const useCompletion = ( {
	onStreamMessage,
	onCompletionFinished = () => {},
	onStreamError = () => {},
}: UseCompletionProps ) => {
	const completionSource = useRef< EventSource | null >( null );
	const previousContent = useRef< string >( '' );
	const [ completionActive, setCompletionActive ] = useState( false );

	const stopCompletion = ( reason: string ) => {
		if ( completionSource.current?.close ) {
			completionSource.current.close();
		}
		onCompletionFinished( reason, previousContent.current );
		completionSource.current = null;
		setCompletionActive( false );
	};

	const onMessage = ( event: MessageEvent ) => {
		if ( event.data === '[DONE]' ) {
			stopCompletion( 'finished' );
			return;
		}

		const data = JSON.parse( event.data );
		const chunk = data.choices[ 0 ].delta.content;
		if ( chunk ) {
			previousContent.current += chunk;
			onStreamMessage( previousContent.current, chunk );
		}
	};

	const onError = ( event: Event ) => {
		// eslint-disable-next-line no-console
		console.debug( 'Streaming error encountered', event );
		stopCompletion( 'error' );
		onStreamError( event );
	};

	const requestCompletion = async ( question: string ) => {
		if ( completionSource.current ) {
			stopCompletion( 'interrupted' );
		}
		previousContent.current = '';

		const suggestionsSource = await askQuestion( question );
		setCompletionActive( true );

		suggestionsSource.addEventListener( 'message', ( e ) =>
			onMessage( e )
		);
		suggestionsSource.addEventListener( 'error', ( event ) =>
			onError( event )
		);

		completionSource.current = suggestionsSource;

		return suggestionsSource;
	};

	return {
		requestCompletion,
		completionActive,
		stopCompletion: stopCompletion.bind( null, 'abort' ),
	};
};

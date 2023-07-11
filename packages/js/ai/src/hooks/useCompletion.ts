/**
 * External dependencies
 */
import { useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getCompletion } from '../utils/text-completion';

type StopReason = 'abort' | 'finished' | 'error' | 'interrupted';

type UseCompletionProps = {
	onStreamMessage?: ( message: string, chunk: string ) => void;
	onCompletionFinished?: (
		reason: StopReason,
		previousContent: string
	) => void;
	onStreamError?: ( error: string ) => void;
	feature?: string;
};

export const useCompletion = ( {
	onStreamMessage = () => {},
	onCompletionFinished = () => {},
	onStreamError = () => {},
	feature,
}: UseCompletionProps ) => {
	const completionSource = useRef< EventSource | null >( null );
	const previousContent = useRef< string >( '' );
	const [ completionActive, setCompletionActive ] = useState( false );

	const stopCompletion = ( reason: StopReason ) => {
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

	const onCompletionError = ( error: string ) => {
		stopCompletion( 'error' );
		onStreamError( error );
		// eslint-disable-next-line no-console
		console.error( `UseCompletion error | ${ error }` );
	};

	const requestCompletion = async (
		prompt: string,
		featureOverride?: string
	) => {
		if (
			! window.JP_CONNECTION_INITIAL_STATE?.connectionStatus?.isActive
		) {
			onCompletionError(
				'You must be connected to Jetpack for text completion'
			);
			return;
		}

		const completionFeature = featureOverride ?? feature;
		if ( completionSource.current ) {
			stopCompletion( 'interrupted' );
		}
		previousContent.current = '';

		let suggestionsSource;

		if ( typeof completionFeature !== 'string' ) {
			onCompletionError(
				'You must provide a feature when requesting a completion'
			);
			return;
		}

		try {
			suggestionsSource = await getCompletion(
				prompt,
				completionFeature
			);
		} catch ( e ) {
			// eslint-disable-next-line no-console
			onCompletionError( 'connection_error' );
			return;
		}

		setCompletionActive( true );

		suggestionsSource.addEventListener( 'message', ( e ) => {
			onMessage( e );
		} );
		suggestionsSource.addEventListener(
			'error',
			( event: MessageEvent ) => {
				// eslint-disable-next-line no-console
				console.debug( 'Streaming error encountered', event );
				onCompletionError(
					typeof event === 'string' ? event : event.data
				);
			}
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

/**
 * External dependencies
 */
import { useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getCompletion, createExtendedError } from '../utils/';

type StopReason = 'abort' | 'finished' | 'error' | 'interrupted';
type ResponseFormat = 'json_object' | 'text';

export type UseCompletionError = Error & { code?: string; cause?: Error };

type UseCompletionProps = {
	onStreamMessage?: ( message: string, chunk: string ) => void;
	onCompletionFinished?: (
		reason: StopReason,
		previousContent: string
	) => void;
	onStreamError?: ( error: UseCompletionError ) => void;
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

	const onCompletionError = ( error: string | Error ) => {
		stopCompletion( 'error' );
		onStreamError( typeof error === 'object' ? error : new Error( error ) );
	};

	const requestCompletion = async (
		prompt: string,
		featureOverride?: string,
		responseFormat?: ResponseFormat
	) => {
		if (
			! window.JP_CONNECTION_INITIAL_STATE?.connectionStatus?.isActive
		) {
			throw createExtendedError(
				'You must be connected to Jetpack for text completion',
				'no_jetpack_connection'
			);
		}

		const completionFeature = featureOverride ?? feature;
		if ( completionSource.current ) {
			stopCompletion( 'interrupted' );
		}
		previousContent.current = '';

		if ( typeof completionFeature !== 'string' ) {
			throw createExtendedError(
				'You must provide a feature when requesting a completion',
				'missing_feature'
			);
		}

		try {
			const suggestionsSource = await getCompletion(
				prompt,
				completionFeature,
				responseFormat ?? 'text'
			);

			setCompletionActive( true );

			suggestionsSource.addEventListener( 'message', ( e ) => {
				onMessage( e );
			} );

			suggestionsSource.addEventListener(
				'error',
				( event: MessageEvent ) => {
					onCompletionError(
						typeof event === 'string' ? event : event.data
					);
				}
			);

			completionSource.current = suggestionsSource;

			return new Promise( ( resolve ) => {
				( completionSource.current as EventSource ).addEventListener(
					'message',
					( event: MessageEvent ) => {
						if ( event.data === '[DONE]' ) {
							resolve( event.data );
						}
					}
				);
			} );
		} catch ( e ) {
			throw createExtendedError(
				'An error occurred while connecting to the completion service',
				'connection_error',
				e as Error
			);
		}
	};

	return {
		requestCompletion,
		completionActive,
		stopCompletion: stopCompletion.bind( null, 'abort' ),
	};
};

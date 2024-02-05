/**
 * Internal dependencies
 */
import { BackgroundRemovalLink } from './background-removal-link';
import { getCurrentAttachmentDetails } from './image_utils';
import { FILENAME_APPEND, LINK_CONTAINER_ID } from './constants';
import { renderWrappedComponent } from '../utils';

( () => {
	if ( ! window.JP_CONNECTION_INITIAL_STATE?.connectionStatus?.isActive ) {
		return;
	}

	const _previous = wp.media.view.Attachment.Details.prototype;

	wp.media.view.Attachment.Details = wp.media.view.Attachment.Details.extend(
		{
			initialize() {
				_previous.initialize.call( this );

				setTimeout( () => {
					renderWrappedComponent(
						BackgroundRemovalLink,
						document.body.querySelector( `#${ LINK_CONTAINER_ID }` )
					);
				}, 0 );
			},
			template( view: { id: number } ) {
				const previousHtml = _previous.template.call( this, view );
				const dom = document.createElement( 'div' );
				dom.innerHTML = previousHtml;
				const attachmentDetails = getCurrentAttachmentDetails( dom );

				if ( attachmentDetails.filename?.includes( FILENAME_APPEND ) ) {
					return dom.innerHTML;
				}

				const details = dom.querySelector( '.details' );
				const reactApp = document.createElement( 'div' );
				reactApp.id = LINK_CONTAINER_ID;
				details?.appendChild( reactApp );

				return dom.innerHTML;
			},
		}
	);
} )();

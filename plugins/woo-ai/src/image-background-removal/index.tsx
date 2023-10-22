/**
 * External dependencies
 */
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore createRoot included for future compatibility
// eslint-disable-next-line @woocommerce/dependency-group
import { render, createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { BackgroundRemovalLink } from './background-removal-link';
import { getCurrentAttachmentDetails } from './image_utils';
import { FILENAME_APPEND, LINK_CONTAINER_ID } from './constants';

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
					const root = document.body.querySelector(
						`#${ LINK_CONTAINER_ID }`
					);
					if ( ! root ) {
						return;
					}
					if ( createRoot ) {
						createRoot( root ).render( <BackgroundRemovalLink /> );
					} else {
						render( <BackgroundRemovalLink />, root );
					}
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

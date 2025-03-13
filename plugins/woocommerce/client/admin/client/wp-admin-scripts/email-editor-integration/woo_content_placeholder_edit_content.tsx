/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import { useEntityRecord, Post } from '@wordpress/core-data';
import { EntityRecordResolution } from '@wordpress/core-data/build-types/hooks/use-entity-record';

const getEmailType = ( value: string ) => {
	return window.WooCommerceEmailEditor.email_types.find(
		( email_type ) => email_type.value === value
	)?.id;
};

const updateiFrameSource = (
	iframeRef: React.RefObject< HTMLIFrameElement >,
	url: string
) => {
	// Update iframe src using replace to avoid polluting browser history
	iframeRef?.current?.contentWindow?.location.replace( url );
};

const DEFAULT_EMAIL_TYPE = 'WC_Email_Customer_Processing_Order';

export function WooContentPlaceholderEditContent() {
	const { current_post_id, current_post_type } =
		window.WooCommerceEmailEditor;
	const { editedRecord: emailPost, hasResolved } = useEntityRecord(
		'postType',
		current_post_type,
		current_post_id
	) as EntityRecordResolution< Post >;
	const iframeRef = useRef< HTMLIFrameElement | null >( null );

	const previewUrlBase = window.WooCommerceEmailEditor?.block_preview_url;

	useEffect( () => {
		if ( ! hasResolved ) {
			return;
		}

		// current email type
		const currentEmailType = getEmailType( emailPost?.slug || '' );
		if ( currentEmailType && iframeRef.current ) {
			updateiFrameSource(
				iframeRef,
				`${ previewUrlBase }&type=${ currentEmailType }`
			);
		}
	}, [ hasResolved, emailPost, iframeRef, previewUrlBase ] );

	return (
		<div>
			<iframe
				style={ { width: '100%', height: '750px' } }
				ref={ iframeRef }
				src={ `${ previewUrlBase }&type=${ DEFAULT_EMAIL_TYPE }` }
				title={ __( 'Email preview frame', 'woocommerce' ) }
			/>
		</div>
	);
}

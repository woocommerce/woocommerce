wp.media.view.Attachment.Details = wp.media.view.Attachment.Details.extend( {
	template( view ) {
		const html = wp.media.template( 'attachment-details' )( view );
		const dom = document.createElement( 'div' );
		dom.innerHTML = html;

		const details = dom.querySelector( '.details' );
		const reactApp = document.createElement( 'div' );
		reactApp.id = 'woocommerce-ai-app-remove-background-button';
		details.appendChild( reactApp );

		return dom.innerHTML;
	},
} );

type AttachmentDetails = {
	url?: string;
	filename?: string;
};

export const getCurrentAttachmentDetails = (
	node: HTMLElement | Document = document
): AttachmentDetails => {
	const details: AttachmentDetails = {};

	const url = (
		node.querySelector(
			'.attachment-details-copy-link'
		) as HTMLInputElement | null
	 )?.value;

	if ( url ) {
		details.url = url;
	}

	details.filename = url?.split( '/' ).pop()?.split( '.' )[ 0 ] ?? '';

	return details;
};

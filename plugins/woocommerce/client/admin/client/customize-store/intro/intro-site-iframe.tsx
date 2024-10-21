export const IntroSiteIframe = ( { siteUrl }: { siteUrl: string } ) => {
	return (
		<iframe
			className="preview-iframe"
			src={ siteUrl }
			title="Preview"
			tabIndex={ -1 }
		></iframe>
	);
};

/**
 * External dependencies
 */
type GoogleSearchPreviewProps = {
	title: string;
	description: string;
	url: string;
	siteTitle: string;
	siteIcon?: string;
};

export function GoogleSearchPreview( {
	title,
	description,
	url,
	siteTitle,
	siteIcon,
}: GoogleSearchPreviewProps ) {
	return (
		<div className="woocommerce-google-search-preview">
			<div className="woocommerce-google-search-preview__meta">
				{ siteIcon ? (
					<img
						src={ siteIcon }
						alt=""
						className="woocommerce-google-search-preview__icon"
					/>
				) : null }
				<div>
					<div className="woocommerce-google-search-preview__site-title">
						{ siteTitle }
					</div>
					<div className="woocommerce-google-search-preview__url">
						{ url }
					</div>
				</div>
			</div>
			<div className="woocommerce-google-search-preview__title">
				{ title }
			</div>
			<div className="woocommerce-google-search-preview__description">
				{ description }
			</div>
		</div>
	);
}

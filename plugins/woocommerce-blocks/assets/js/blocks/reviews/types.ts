export interface PreviewReviews {
	id: number;
	date_created: string;
	formatted_date_created: string;
	date_created_gmt: string;
	product_id: number;
	product_name: string;
	product_permalink: string;
	reviewer: string;
	review: string;
	reviewer_avatar_urls: { [ id: number ]: string };
	rating: number;
	verified: boolean;
}

export interface Attributes {
	categoryIds?: number[];
	editMode?: boolean;
	imageType?: string;
	orderby?: string;
	productId?: number;
	reviewsOnLoadMore?: number;
	reviewsOnPageLoad?: number;
	showLoadMore?: boolean;
	showOrderby?: boolean;
	showProductName?: boolean;
	showReviewDate?: boolean;
	showReviewerName?: boolean;
	showReviewImage?: boolean;
	showReviewRating?: boolean;
	showReviewContent?: boolean;
	previewReviews?: PreviewReviews[];
}

export interface EditorContainerBlockProps {
	attributes: Attributes;
	icon: JSX.Element;
	name: string;
	noReviewsPlaceholder: React.ElementType;
	className?: string;
}

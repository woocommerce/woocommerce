export interface ProductsByTagBlockProps {
	attributes: {
		tags: number[];
		tagOperator: string;
		columns: number;
		rows: number;
		alignButtons: boolean;
		contentVisibility: {
			title: boolean;
			price: boolean;
			rating: boolean;
			button: boolean;
			quantity?: boolean;
			description?: boolean;
			category?: boolean;
			image: boolean;
		};
		orderby: string;
		stockStatus: string[];
		isPreview: boolean;
	};
	name: string;
	setAttributes: (
		attributes: Partial< ProductsByTagBlockProps[ 'attributes' ] >
	) => void;
	debouncedSpeak: ( message: string ) => void;
}

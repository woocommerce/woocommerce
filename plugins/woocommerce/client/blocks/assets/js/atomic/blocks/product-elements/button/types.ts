/**
 * External dependencies
 */
import { ProductEntityResponse } from '@woocommerce/entities';

interface WithClass {
	className: string;
}

interface WithStyle {
	style: Record< string, unknown >;
}

export interface BlockAttributes {
	className?: string | undefined;
	textAlign?: string | undefined;
	isDescendentOfQueryLoop?: boolean | undefined;
	isDescendentOfSingleProductBlock?: boolean | undefined;
	width?: number | undefined;
	// eslint-disable-next-line @typescript-eslint/naming-convention
	'woocommerce/isDescendantOfAddToCartWithOptions'?: boolean | undefined;
	blockClientId?: string;
	product?: ProductEntityResponse | undefined;
	isAdmin?: boolean | undefined;
}

export interface AddToCartProductDetails {
	url: string;
	description: string;
	text: string;
	single_text: string;
}

export interface AddToCartButtonPlaceholderAttributes {
	className: string;
	style: React.CSSProperties;
	isLoading: boolean;
	blockClientId?: string;
}

export interface AddToCartButtonAttributes {
	className: string;
	style: React.CSSProperties;
	isDescendantOfAddToCartWithOptions: boolean | undefined;
	product: {
		id: number;
		type: string;
		permalink: string;
		add_to_cart: AddToCartProductDetails;
		has_options: boolean;
		is_purchasable: boolean;
		is_in_stock: boolean;
		button_text: string;
	};
	textAlign?: ( WithClass & WithStyle ) | undefined;
}

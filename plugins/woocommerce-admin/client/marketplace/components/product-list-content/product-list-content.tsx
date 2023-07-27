/* eslint-disable @typescript-eslint/no-unused-vars */
/**
 * Internal dependencies
 */
import ProductCard from '../product-card/product-card';
import './product-list-content.scss';
export interface Product {
	id?: number;
	title: string;
	description: string;
	vendorName: string;
	vendorUrl: string;
	icon: string;
	url: string;
	price: string | number;
	productType?: string;
	averageRating?: number | null;
	reviewsCount?: number | null;
	currency?: string;
}

export default function ProductListContent(): JSX.Element {
	return (
		<div className="woocommerce-marketplace__product-list-content"></div>
	);
}

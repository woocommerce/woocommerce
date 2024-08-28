/**
 * External dependencies
 */
import { PartialProduct } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	getProductStockStatus,
	getProductStockStatusClass,
} from '../get-product-stock-status';

const products = [
	{
		status: 'publish',
	} as PartialProduct,
	{
		status: 'publish',
		stock_status: 'outofstock',
	} as PartialProduct,
	{
		manage_stock: true,
		stock_quantity: 15,
		status: 'publish',
		stock_status: 'instock',
	} as PartialProduct,
	{
		manage_stock: true,
		status: 'publish',
		stock_status: 'instock',
	} as PartialProduct,
	{
		manage_stock: true,
		stock_quantity: 5,
		status: 'publish',
		stock_status: 'instock',
	} as PartialProduct,
	{
		manage_stock: true,
		stock_quantity: 1,
		status: 'publish',
		stock_status: 'instock',
	} as PartialProduct,
	{
		manage_stock: false,
		status: 'publish',
		stock_status: 'instock',
	} as PartialProduct,
	{
		manage_stock: false,
		status: 'publish',
		stock_status: 'onbackorder',
	} as PartialProduct,
	{
		manage_stock: false,
		status: 'publish',
		stock_status: 'outofstock',
	} as PartialProduct,
];

describe( 'getProductStockStatus', () => {
	it( 'should return `In stock` status when the stock is not being managed and there is no stock status', () => {
		const status = getProductStockStatus( products[ 0 ] );
		expect( status ).toBe( 'In stock' );
	} );

	it( 'should return the stock status when there is a stock status and the stock is not being managed', () => {
		const status = getProductStockStatus( products[ 1 ] );
		expect( status ).toBe( 'Out of stock' );
	} );

	it( 'should return the stock quantity when the stock is being managed', () => {
		const status = getProductStockStatus( products[ 2 ] );
		expect( status ).toBe( 15 );
	} );

	it( 'should return stock quantity = 0 when the stock is being managed but there is no a stock quantity', () => {
		const status = getProductStockStatus( products[ 3 ] );
		expect( status ).toBe( 0 );
	} );
} );

describe( 'getProductStockStatusClass', () => {
	it( 'should return an empty string when the stock is not being managed and there is no stock status', () => {
		const status = getProductStockStatusClass( products[ 0 ] );
		expect( status ).toBe( '' );
	} );

	it( 'should return `green` when the stock is being managed and the stock quantity is higher or equal than 10', () => {
		const status = getProductStockStatusClass( products[ 2 ] );
		expect( status ).toBe( 'green' );
	} );

	it( 'should return `yellow` when the stock is being managed and the stock quantity is lower than 10 but higher than 2', () => {
		const status = getProductStockStatusClass( products[ 4 ] );
		expect( status ).toBe( 'yellow' );
	} );

	it( 'should return `red` when the stock is being managed and the stock quantity is lower or equal than 2', () => {
		const status = getProductStockStatusClass( products[ 5 ] );
		expect( status ).toBe( 'red' );
	} );

	it( 'should return `red` when the stock is being managed but there is no a stock quantity', () => {
		const status = getProductStockStatusClass( products[ 3 ] );
		expect( status ).toBe( 'red' );
	} );

	it( 'should return `green` when the stock is not being managed and the stock status is `instock`', () => {
		const status = getProductStockStatusClass( products[ 6 ] );
		expect( status ).toBe( 'green' );
	} );

	it( 'should return `yellow` when the stock is not being managed and the stock status is `onbackorder`', () => {
		const status = getProductStockStatusClass( products[ 7 ] );
		expect( status ).toBe( 'yellow' );
	} );

	it( 'should return `red` when the stock is not being managed and the stock status is `outofstock`', () => {
		const status = getProductStockStatusClass( products[ 8 ] );
		expect( status ).toBe( 'red' );
	} );
} );

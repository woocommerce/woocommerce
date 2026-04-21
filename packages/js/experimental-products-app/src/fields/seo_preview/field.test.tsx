/**
 * External dependencies
 */
import '@testing-library/jest-dom';

import { render, screen } from '@testing-library/react';

import React from 'react';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { fieldExtensions } from './field';

jest.mock( '@wordpress/core-data', () => ( {
	store: 'core',
} ) );

const mockSiteData = {
	title: 'My Store',
	url: 'https://mystore.com',
};

const mockBaseData = {
	site_icon_url: 'https://mystore.com/wp-content/uploads/site-icon.png',
};

jest.mock( '@wordpress/data', () => ( {
	useSelect: ( selector: ( select: unknown ) => unknown ) => {
		const select = () => ( {
			getSite: () => mockSiteData,
			getEntityRecord: () => mockBaseData,
		} );
		return selector( select );
	},
} ) );

jest.mock( '../utils/html', () => ( {
	convertHtmlToPlainText: ( text: string ) => text.replace( /<[^>]*>/g, '' ),
} ) );

jest.mock( '../components/google-search-preview', () => ( {
	GoogleSearchPreview: ( {
		title,
		description,
		url,
		siteTitle,
		siteIcon,
	}: {
		title: string;
		description: string;
		url: string;
		siteTitle: string;
		siteIcon: string;
	} ) => (
		<div data-testid="google-search-preview">
			<span data-testid="preview-title">{ title }</span>
			<span data-testid="preview-description">{ description }</span>
			<span data-testid="preview-url">{ url }</span>
			<span data-testid="preview-site-title">{ siteTitle }</span>
			<span data-testid="preview-site-icon">{ siteIcon }</span>
		</div>
	),
} ) );

type RenderProps = {
	item: Partial< ProductEntityRecord >;
	field: { id: string };
};

const Render =
	fieldExtensions.render as unknown as React.ComponentType< RenderProps >;

const renderPreview = ( item: Partial< ProductEntityRecord > ) => {
	return render(
		<Render
			item={ item as ProductEntityRecord }
			field={ { id: 'seo_preview' } }
		/>
	);
};

describe( 'seo_preview field', () => {
	it( 'displays product name when no SEO title is set', () => {
		renderPreview( { name: 'Test Product', seo_title: '' } );
		expect( screen.getByTestId( 'preview-title' ) ).toHaveTextContent(
			'Test Product'
		);
	} );

	it( 'displays custom SEO title when set', () => {
		renderPreview( {
			name: 'Test Product',
			seo_title: 'Custom SEO Title',
		} );
		expect( screen.getByTestId( 'preview-title' ) ).toHaveTextContent(
			'Custom SEO Title'
		);
	} );

	it( 'passes SEO description to GoogleSearchPreview', () => {
		renderPreview( {
			name: 'Test Product',
			seo_description: 'This is a custom description.',
		} );
		expect( screen.getByTestId( 'preview-description' ) ).toHaveTextContent(
			'This is a custom description.'
		);
	} );

	it( 'falls back to short description when no SEO description is set', () => {
		renderPreview( {
			name: 'Test Product',
			seo_description: '',
			short_description: 'A great product for testing.',
		} );
		expect( screen.getByTestId( 'preview-description' ) ).toHaveTextContent(
			'A great product for testing.'
		);
	} );

	it( 'passes empty description when no SEO description or short description', () => {
		renderPreview( { name: 'Test Product', seo_description: '' } );
		expect( screen.getByTestId( 'preview-description' ) ).toHaveTextContent(
			''
		);
	} );

	it( 'passes site name to GoogleSearchPreview', () => {
		renderPreview( { name: 'Test Product' } );
		expect( screen.getByTestId( 'preview-site-title' ) ).toHaveTextContent(
			'My Store'
		);
	} );

	it( 'passes product permalink as URL', () => {
		renderPreview( {
			name: 'Test Product',
			permalink: 'https://example.com/product/test',
		} );
		expect( screen.getByTestId( 'preview-url' ) ).toHaveTextContent(
			'https://example.com/product/test'
		);
	} );

	it( 'falls back to site URL when no permalink', () => {
		renderPreview( { name: 'Test Product' } );
		expect( screen.getByTestId( 'preview-url' ) ).toHaveTextContent(
			'https://mystore.com'
		);
	} );

	it( 'falls back to site name when no product name or SEO title', () => {
		renderPreview( { name: '', seo_title: '' } );
		expect( screen.getByTestId( 'preview-title' ) ).toHaveTextContent(
			'My Store'
		);
	} );

	it( 'passes site icon URL to GoogleSearchPreview', () => {
		renderPreview( { name: 'Test Product' } );
		expect( screen.getByTestId( 'preview-site-icon' ) ).toHaveTextContent(
			'https://mystore.com/wp-content/uploads/site-icon.png'
		);
	} );
} );

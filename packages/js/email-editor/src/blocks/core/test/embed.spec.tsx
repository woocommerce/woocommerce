/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { applyFilters } from '@wordpress/hooks';
import {
	registerBlockType,
	unregisterBlockType,
	getBlockType,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { enhanceEmbedBlock, isSupportedProviderUrl } from '../embed';
import { clearAllEmailHooks } from '../../../config-tools/filters';
import { restoreAllModifiedBlockSettings } from '../../../config-tools/block-config';

jest.mock( '@wordpress/components', () => ( {
	Notice: ( { children }: { children: React.ReactNode } ) => (
		<div role="alert">{ children }</div>
	),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn( () => ( {
		__unstableMarkNextChangeAsNotPersistent: jest.fn(),
	} ) ),
} ) );

describe( 'isSupportedProviderUrl', () => {
	it( 'accepts URLs from providers supported by the email renderer', () => {
		expect(
			isSupportedProviderUrl( 'https://www.youtube.com/watch?v=abc' )
		).toBe( true );
		expect( isSupportedProviderUrl( 'https://youtu.be/abc' ) ).toBe( true );
		expect( isSupportedProviderUrl( 'https://vimeo.com/123' ) ).toBe(
			true
		);
		expect( isSupportedProviderUrl( 'https://videopress.com/v/abc' ) ).toBe(
			true
		);
	} );

	it( 'rejects audio provider URLs (rendered as a plain link button)', () => {
		expect(
			isSupportedProviderUrl( 'https://open.spotify.com/track/abc' )
		).toBe( false );
		expect(
			isSupportedProviderUrl( 'https://soundcloud.com/forss/flickermood' )
		).toBe( false );
	} );

	it( 'rejects URLs from unsupported providers', () => {
		expect(
			isSupportedProviderUrl( 'https://twitter.com/user/status/1' )
		).toBe( false );
		expect(
			isSupportedProviderUrl( 'https://example.com/youtube.com' )
		).toBe( false );
		expect( isSupportedProviderUrl( 'https://notyoutube.com/watch' ) ).toBe(
			false
		);
		expect( isSupportedProviderUrl( 'not-a-url' ) ).toBe( false );
	} );
} );

describe( 'enhanceEmbedBlock', () => {
	beforeEach( () => {
		registerBlockType( 'core/embed', {
			apiVersion: 3,
			title: 'Embed',
			category: 'embed',
			attributes: {},
			variations: [
				{
					name: 'youtube',
					title: 'YouTube',
					attributes: {
						providerNameSlug: 'youtube',
						responsive: true,
					},
				},
				{ name: 'spotify', title: 'Spotify' },
				{ name: 'twitter', title: 'Twitter' },
				{ name: 'facebook', title: 'Facebook' },
				{ name: 'wordpress', title: 'WordPress' },
			],
		} );
		enhanceEmbedBlock();
	} );

	afterEach( () => {
		clearAllEmailHooks();
		restoreAllModifiedBlockSettings();
		unregisterBlockType( 'core/embed' );
	} );

	it( 'removes all variations except video providers and wordpress', () => {
		const variations = getBlockType( 'core/embed' )?.variations ?? [];
		expect( variations.map( ( v ) => v.name ) ).toEqual( [
			'youtube',
			'wordpress',
		] );
	} );

	const OriginalBlockEdit = () => <div>original edit</div>;
	const renderFiltered = ( name: string, attributes: object ) => {
		const FilteredBlockEdit = applyFilters(
			'editor.BlockEdit',
			OriginalBlockEdit
		) as React.ElementType;
		render(
			<FilteredBlockEdit
				name={ name }
				attributes={ attributes }
				setAttributes={ jest.fn() }
			/>
		);
	};

	it( 'shows a warning for embeds from unsupported providers', () => {
		renderFiltered( 'core/embed', {
			url: 'https://twitter.com/user/status/1',
		} );
		expect( screen.getByRole( 'alert' ) ).toHaveTextContent(
			'This embed is not supported in emails. It will be sent as a link.'
		);
		expect( screen.getByText( 'original edit' ) ).toBeInTheDocument();
	} );

	it( 'warns for audio provider embeds', () => {
		renderFiltered( 'core/embed', {
			url: 'https://open.spotify.com/track/abc',
		} );
		expect( screen.getByRole( 'alert' ) ).toBeInTheDocument();
	} );

	it( 'does not warn for supported providers', () => {
		renderFiltered( 'core/embed', {
			url: 'https://vimeo.com/123',
		} );
		expect( screen.queryByRole( 'alert' ) ).not.toBeInTheDocument();
	} );

	it( 'does not warn for WordPress embeds', () => {
		renderFiltered( 'core/embed', {
			url: 'https://wordpress.org/news/post',
			type: 'wp-embed',
		} );
		expect( screen.queryByRole( 'alert' ) ).not.toBeInTheDocument();
	} );

	it( 'does not warn for embeds without a URL', () => {
		renderFiltered( 'core/embed', {} );
		expect( screen.queryByRole( 'alert' ) ).not.toBeInTheDocument();
	} );

	it( 'removes the responsive attribute from kept variations', () => {
		const variations = getBlockType( 'core/embed' )?.variations ?? [];
		const youtube = variations.find( ( v ) => v.name === 'youtube' );
		expect( youtube?.attributes ).toEqual( {
			providerNameSlug: 'youtube',
		} );
	} );

	it( 'resets the responsive attribute on embeds from stored content', () => {
		const setAttributes = jest.fn();
		const FilteredBlockEdit = applyFilters(
			'editor.BlockEdit',
			OriginalBlockEdit
		) as React.ElementType;
		render(
			<FilteredBlockEdit
				name="core/embed"
				attributes={ {
					url: 'https://vimeo.com/123',
					responsive: true,
				} }
				setAttributes={ setAttributes }
			/>
		);
		expect( setAttributes ).toHaveBeenCalledWith( { responsive: false } );
	} );

	it( 'does not reset the responsive attribute when it is already false', () => {
		const setAttributes = jest.fn();
		const FilteredBlockEdit = applyFilters(
			'editor.BlockEdit',
			OriginalBlockEdit
		) as React.ElementType;
		render(
			<FilteredBlockEdit
				name="core/embed"
				attributes={ {
					url: 'https://vimeo.com/123',
					responsive: false,
				} }
				setAttributes={ setAttributes }
			/>
		);
		expect( setAttributes ).not.toHaveBeenCalled();
	} );

	it( 'does not affect other blocks', () => {
		renderFiltered( 'core/paragraph', {
			url: 'https://twitter.com/user/status/1',
		} );
		expect( screen.queryByRole( 'alert' ) ).not.toBeInTheDocument();
	} );
} );

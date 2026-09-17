/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { registerCoreBlocks } from '@wordpress/block-library';
import { createBlock } from '@wordpress/blocks';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import type { ComponentType } from 'react';
import type { WP_REST_API_Category as Category } from 'wp-types';

/**
 * Internal dependencies
 */
import FeaturedCategory from '../block';

const mockControls = jest.fn< null, [ { triggerUrlUpdate: () => void } ] >(
	() => null
);

jest.mock( '@woocommerce/block-hocs', () => ( {
	withCategory: ( Component: ComponentType ) => Component,
} ) );
jest.mock( '../../with-edit-mode', () => ( {
	withEditMode:
		() => () => ( props: Parameters< typeof mockControls >[ 0 ] ) =>
			mockControls( props ),
} ) );

const clothing = {
	id: 1,
	permalink: 'https://example.com/product-category/clothing/',
} as Category;
const music = {
	id: 2,
	permalink: 'https://example.com/product-category/music/',
} as Category;

beforeAll( () => registerCoreBlocks() );

describe( 'Featured Category uses the existing button-link behavior', () => {
	function setup( firstUrl = clothing.permalink ) {
		const registry = createRegistry();
		registry.register( blockEditorStore );
		const buttons = [ firstUrl, clothing.permalink ].map( ( url ) =>
			createBlock( 'core/button', { text: 'Shop now', url } )
		);
		const parent = createBlock( 'core/group', {}, [
			createBlock( 'core/cover', {}, [
				createBlock( 'core/buttons', {}, buttons ),
			] ),
		] );
		registry.dispatch( blockEditorStore ).resetBlocks( [ parent ] );
		const view = ( category?: Category ) => (
			<RegistryProvider value={ registry }>
				<FeaturedCategory
					clientId={ parent.clientId }
					attributes={ {
						categoryId: category?.id || 0,
						layout: 'cover',
					} }
					category={ category }
					isLoading={ false }
					setAttributes={ jest.fn() }
				/>
			</RegistryProvider>
		);
		const result = render( view( clothing ) );
		return {
			...result,
			view,
			triggerUrlUpdate: () => {
				act(
					() => mockControls.mock.lastCall?.[ 0 ].triggerUrlUpdate()
				);
			},
			getUrls: () =>
				buttons.map(
					( button ) =>
						registry
							.select( blockEditorStore )
							.getBlock( button.clientId )?.attributes.url
				),
		};
	}

	it( 'updates only the first button inside Cover when selecting a category', () => {
		const { triggerUrlUpdate, rerender, view, getUrls } = setup();
		triggerUrlUpdate();
		rerender( view( music ) );
		expect( getUrls() ).toEqual( [ music.permalink, clothing.permalink ] );
	} );

	it( 'keeps the link while empty and updates it when another category is selected', () => {
		const { triggerUrlUpdate, rerender, view, getUrls } = setup();
		triggerUrlUpdate();
		rerender( view() );
		expect( getUrls() ).toEqual( [
			clothing.permalink,
			clothing.permalink,
		] );
		triggerUrlUpdate();
		rerender( view( music ) );
		expect( getUrls() ).toEqual( [ music.permalink, clothing.permalink ] );
	} );

	it( 'does not fill an empty first button or skip ahead to another button', () => {
		const { triggerUrlUpdate, rerender, view, getUrls } = setup( '' );
		triggerUrlUpdate();
		rerender( view( music ) );
		expect( getUrls() ).toEqual( [ '', clothing.permalink ] );
	} );

	it( 'retains trunk behavior of replacing a custom first-button URL', () => {
		const { triggerUrlUpdate, getUrls } = setup(
			'https://example.com/custom/'
		);
		triggerUrlUpdate();
		expect( getUrls() ).toEqual( [
			clothing.permalink,
			clothing.permalink,
		] );
	} );
} );

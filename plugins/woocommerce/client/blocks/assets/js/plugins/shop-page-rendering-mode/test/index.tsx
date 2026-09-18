/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ShopPageRenderingMode } from '../index';

jest.mock( '@wordpress/data', () => ( {
	useDispatch: jest.fn(),
	useSelect: jest.fn(),
} ) );
jest.mock( '@wordpress/editor', () => ( { store: 'core/editor' } ) );
jest.mock( '@wordpress/core-data', () => ( { store: 'core' } ) );
jest.mock( '@wordpress/plugins', () => ( {
	getPlugin: jest.fn(),
	registerPlugin: jest.fn(),
} ) );
jest.mock( '@woocommerce/settings', () => ( {
	STORE_PAGES: { shop: { id: 123 } },
} ) );

describe( 'Shop page rendering mode', () => {
	const setRenderingMode = jest.fn();
	let postId: number | string;
	let postType: string;
	let isBlockTheme: boolean | undefined;
	let isEditorReady: boolean;
	let renderingMode: string;
	let templateId: string | undefined;

	beforeEach( () => {
		jest.clearAllMocks();
		setRenderingMode.mockImplementation( ( mode: string ) => {
			renderingMode = mode;
		} );
		postId = 123;
		postType = 'page';
		isBlockTheme = true;
		isEditorReady = true;
		renderingMode = 'post-only';
		templateId = 'theme//archive-product';
		( useDispatch as jest.Mock ).mockReturnValue( { setRenderingMode } );
		( useSelect as jest.Mock ).mockImplementation( ( callback ) =>
			callback( ( store: string ) =>
				store === 'core'
					? {
							getCurrentTheme: () => ( {
								is_block_theme: isBlockTheme,
							} ),
					  }
					: {
							getCurrentPostId: () => postId,
							getCurrentPostType: () => postType,
							__unstableIsEditorReady: () => isEditorReady,
							getRenderingMode: () => renderingMode,
							getCurrentTemplateId: () => templateId,
					  }
			)
		);
	} );

	it( 'enables template-locked rendering for the Shop page', () => {
		render( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).toHaveBeenCalledWith( 'template-locked' );
	} );

	it( 'sets the mode after the provider initializes its default mode', () => {
		templateId = undefined;
		function EditorProvider() {
			const [ , setInitialized ] = useState( false );
			useEffect( () => {
				templateId = 'theme//archive-product';
				renderingMode = 'post-only';
				setInitialized( true );
			}, [] );
			return <ShopPageRenderingMode />;
		}

		render( <EditorProvider /> );
		expect( renderingMode ).toBe( 'template-locked' );
	} );

	it( 'waits until the editor is ready before setting the mode', () => {
		isEditorReady = false;
		const { rerender } = render( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).not.toHaveBeenCalled();

		isEditorReady = true;
		rerender( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).toHaveBeenCalledWith( 'template-locked' );
		expect( setRenderingMode ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'waits until the provider sets the current template', () => {
		templateId = undefined;
		const { rerender } = render( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).not.toHaveBeenCalled();

		templateId = 'theme//archive-product';
		rerender( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).toHaveBeenCalledWith( 'template-locked' );
	} );

	it( 'enables the mode when navigating to the Shop page', () => {
		postId = 456;
		const { rerender } = render( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).not.toHaveBeenCalled();

		postId = '123';
		rerender( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).toHaveBeenCalledWith( 'template-locked' );
	} );

	it( 'reapplies the mode when editor initialization resets it', () => {
		const { rerender } = render( <ShopPageRenderingMode /> );
		renderingMode = 'template-locked';
		rerender( <ShopPageRenderingMode /> );
		setRenderingMode.mockClear();

		renderingMode = 'post-only';
		rerender( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).toHaveBeenCalledWith( 'template-locked' );
		expect( setRenderingMode ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not dispatch when the Shop page is already template-locked', () => {
		renderingMode = 'template-locked';
		render( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).not.toHaveBeenCalled();
	} );

	it( 'waits until the block theme has resolved', () => {
		isBlockTheme = undefined;
		const { rerender } = render( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).not.toHaveBeenCalled();

		isBlockTheme = true;
		rerender( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).toHaveBeenCalledWith( 'template-locked' );
	} );

	it( 'stops changing the mode when navigating away from the Shop page', () => {
		const { rerender } = render( <ShopPageRenderingMode /> );
		setRenderingMode.mockClear();

		postId = 456;
		rerender( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).not.toHaveBeenCalled();
	} );

	it( 'preserves the rendering mode under classic themes', () => {
		isBlockTheme = false;
		render( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).not.toHaveBeenCalled();
	} );

	it( 'does not change rendering when editing a template', () => {
		postType = 'wp_template';
		render( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).not.toHaveBeenCalled();
	} );

	it( 'does not reset the mode on unrelated rerenders', () => {
		const { rerender } = render( <ShopPageRenderingMode /> );
		setRenderingMode.mockClear();

		rerender( <ShopPageRenderingMode /> );
		expect( setRenderingMode ).not.toHaveBeenCalled();
	} );
} );

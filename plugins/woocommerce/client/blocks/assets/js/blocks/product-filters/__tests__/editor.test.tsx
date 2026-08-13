/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import { getBlockAttributes } from '@wordpress/blocks';
import type { BlockEditProps } from '@wordpress/blocks';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { Edit } from '../edit';
import { migrateOverlayAttributes } from '../deprecated';
import { Save } from '../save';
import type { BlockAttributes } from '../types';

jest.mock( '@wordpress/block-editor', () => {
	const useBlockProps = ( props: object ) => props;
	useBlockProps.save = ( props: object ) => props;
	const useInnerBlocksProps = { save: ( props: object ) => props };
	return {
		InnerBlocks: () => <div data-testid="inner-blocks" />,
		InspectorControls: ( { children }: { children: ReactNode } ) => (
			<aside>{ children }</aside>
		),
		useBlockProps,
		useInnerBlocksProps,
	};
} );

const editProps = (
	attributes: BlockAttributes,
	setAttributes = jest.fn()
): BlockEditProps< BlockAttributes > =>
	( {
		attributes,
		setAttributes,
		isSelected: true,
	} ) as unknown as BlockEditProps< BlockAttributes >;

describe( 'Product Filters editor and serialization', () => {
	it.each( [
		[
			'off to mobile',
			{ isPreview: false, overlayMode: 'off' as const },
			'Off',
			'Mobile only',
			{ overlayMode: 'mobile' },
		],
		[
			'mobile to all devices',
			{ isPreview: false, overlayMode: 'mobile' as const },
			'Mobile only',
			'All devices',
			{ overlayMode: 'all' },
		],
		[
			'all devices to off',
			{ isPreview: false, overlayMode: 'all' as const },
			'All devices',
			'Off',
			{ overlayMode: 'off' },
		],
	] )(
		'writes the derived overlay mode: %s',
		( _label, attributes, selected, target, expected ) => {
			const setAttributes = jest.fn();
			render(
				<Edit
					{ ...editProps(
						attributes as BlockAttributes,
						setAttributes
					) }
				/>
			);

			expect( screen.getByLabelText( selected ) ).toBeChecked();
			expect(
				screen.getByText(
					'When on, filters are hidden behind a button instead of showing on the page.'
				)
			).toBeVisible();
			fireEvent.click( screen.getByLabelText( target ) );
			expect( setAttributes ).toHaveBeenCalledWith( expected );
		}
	);

	it( 'shows position only for desktop overlay and preserves its value', () => {
		const { rerender } = render(
			<Edit
				{ ...editProps( {
					isPreview: false,
					overlayMode: 'mobile',
					desktopOverlayPosition: 'right',
				} ) }
			/>
		);
		expect(
			screen.queryByText( 'Desktop overlay position' )
		).not.toBeInTheDocument();

		rerender(
			<Edit
				{ ...editProps( {
					isPreview: false,
					overlayMode: 'all',
					desktopOverlayPosition: 'right',
				} ) }
			/>
		);
		expect( screen.getByText( 'Desktop overlay position' ) ).toBeVisible();
		expect( screen.getByLabelText( 'Right' ) ).toBeChecked();
	} );

	it.each( [
		[ { isPreview: false }, 'wc-block-product-filters' ],
		[
			{ isPreview: false, overlayMode: 'off' as const },
			'wc-block-product-filters is-filter-drawer-disabled',
		],
		[
			{
				isPreview: false,
				overlayMode: 'all' as const,
				desktopOverlayPosition: 'right' as const,
			},
			'wc-block-product-filters has-desktop-overlay is-desktop-overlay-right',
		],
	] )( 'serializes overlay mode classes', ( attributes, className ) => {
		const { container } = render(
			<Save attributes={ attributes as BlockAttributes } />
		);
		expect( container.firstChild ).toHaveClass( ...className.split( ' ' ) );
		expect( container.firstChild ).toHaveAttribute( 'class', className );
	} );

	it( 'documents parser behavior across the storage change', () => {
		const booleanWithChangedType = getBlockAttributes(
			{
				attributes: {
					showFilterDrawer: {
						type: 'string',
						enum: [ 'off', 'mobile', 'all' ],
					},
				},
			} as never,
			'',
			{ showFilterDrawer: false }
		);
		const legacyMarkupInCurrentWoo = getBlockAttributes(
			{
				attributes: {
					overlayMode: {
						type: 'string',
						enum: [ 'off', 'mobile', 'all' ],
					},
				},
			} as never,
			'',
			{ showFilterDrawer: false }
		);
		const enumMarkupInOldWoo = getBlockAttributes(
			{
				attributes: {
					showFilterDrawer: {
						type: 'boolean',
						default: true,
					},
				},
			} as never,
			'',
			{ overlayMode: 'off' }
		);

		expect( booleanWithChangedType ).toEqual( {
			showFilterDrawer: undefined,
		} );
		expect( legacyMarkupInCurrentWoo ).toEqual( {
			overlayMode: undefined,
		} );
		expect( enumMarkupInOldWoo ).toEqual( { showFilterDrawer: true } );
	} );

	it.each( [
		[ {}, 'mobile' ],
		[ { showFilterDrawer: false }, 'off' ],
		[ { showFilterDrawer: true }, 'mobile' ],
		[ { showFilterDrawer: false, overlayOnDesktop: true }, 'all' ],
	] )( 'migrates legacy overlay attributes: %#', ( legacy, overlayMode ) => {
		expect(
			migrateOverlayAttributes( {
				isPreview: false,
				...legacy,
			} )
		).toEqual( { isPreview: false, overlayMode } );
	} );
} );

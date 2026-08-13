/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import type { BlockEditProps } from '@wordpress/blocks';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { Edit } from '../edit';
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
			{ isPreview: false, showFilterDrawer: false },
			'Off',
			'Mobile only',
			{ showFilterDrawer: true, overlayOnDesktop: false },
		],
		[
			'mobile to all devices',
			{ isPreview: false, showFilterDrawer: true },
			'Mobile only',
			'All devices',
			{ showFilterDrawer: true, overlayOnDesktop: true },
		],
		[
			'conflicting desktop to off',
			{
				isPreview: false,
				showFilterDrawer: false,
				overlayOnDesktop: true,
			},
			'All devices',
			'Off',
			{ showFilterDrawer: false, overlayOnDesktop: false },
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
					showFilterDrawer: true,
					overlayOnDesktop: false,
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
					showFilterDrawer: true,
					overlayOnDesktop: true,
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
			{ isPreview: false, showFilterDrawer: false },
			'wc-block-product-filters is-filter-drawer-disabled',
		],
		[
			{
				isPreview: false,
				showFilterDrawer: false,
				overlayOnDesktop: true,
				desktopOverlayPosition: 'right' as const,
			},
			'wc-block-product-filters has-desktop-overlay is-desktop-overlay-right',
		],
		[
			{
				isPreview: false,
				showFilterDrawer: false,
				overlayOnDesktop: 1,
				desktopOverlayPosition: 'right' as const,
			},
			'wc-block-product-filters is-filter-drawer-disabled',
		],
	] )( 'keeps legacy save classes stable', ( attributes, className ) => {
		const { container } = render(
			<Save attributes={ attributes as BlockAttributes } />
		);
		expect( container.firstChild ).toHaveClass( ...className.split( ' ' ) );
		expect( container.firstChild ).toHaveAttribute( 'class', className );
	} );
} );

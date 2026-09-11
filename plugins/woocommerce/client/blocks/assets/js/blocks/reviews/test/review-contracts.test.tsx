/**
 * External dependencies
 */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import type { ReviewBlockAttributes } from '../attributes';
import FrontendContainerBlock from '../frontend-container-block';
import { getSharedReviewListControls } from '../edit-utils';
import { getDataAttrs, getReviews, getSortArgs } from '../utils';

type FrontendRegistration = {
	getProps: (
		element: HTMLElement,
		index: number
	) => { attributes: Record< string, unknown > };
};

let mockFrontendRegistration: FrontendRegistration | undefined;

jest.mock( '@woocommerce/base-utils', () => ( {
	...jest.requireActual( '@woocommerce/base-utils' ),
	renderFrontend: jest.fn( ( registration ) => {
		mockFrontendRegistration = registration;
	} ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting: jest
		.fn()
		.mockImplementation( ( setting, defaultValue ) => defaultValue ),
} ) );

jest.mock( '../utils', () => ( {
	...jest.requireActual( '../utils' ),
	getReviews: jest.fn(),
} ) );

jest.mock( '@wordpress/components', () => {
	const actual = jest.requireActual( '@wordpress/components' );
	const React = jest.requireActual( 'react' );

	return {
		...actual,
		__experimentalInputControl: ( { label, onChange, value } ) =>
			React.createElement( 'input', {
				'aria-label': label,
				onChange: ( event ) => onChange( event.target.value ),
				value,
			} ),
		__experimentalNumberControl: ( {
			__unstableStateReducer,
			label,
			onChange,
			value,
		} ) =>
			React.createElement( 'input', {
				'aria-label': label,
				onChange: ( event ) => {
					const state = __unstableStateReducer
						? __unstableStateReducer( {
								value: event.target.value,
						  } )
						: { value: event.target.value };
					onChange( state.value );
				},
				type: 'number',
				value,
			} ),
		__experimentalToolsPanelItem: ( { children, label } ) =>
			React.createElement( 'section', { 'aria-label': label }, children ),
		SelectControl: ( { label, onChange, options, value } ) =>
			React.createElement(
				'select',
				{
					'aria-label': label,
					onChange: ( event ) => onChange( event.target.value ),
					value,
				},
				options.map( ( option ) =>
					React.createElement(
						'option',
						{ key: option.value, value: option.value },
						option.label
					)
				)
			),
		ToggleControl: ( { checked, label, onChange } ) =>
			React.createElement( 'input', {
				'aria-label': label,
				checked,
				onChange,
				type: 'checkbox',
			} ),
	};
} );

// Importing the frontend entrypoint registers its selector and hydration mapper.
jest.requireActual( '../frontend' );

const mockGetReviews = getReviews as jest.Mock;
const mockGetSetting = getSetting as jest.Mock;

const createAttributes = (
	overrides: Partial< ReviewBlockAttributes > = {}
): ReviewBlockAttributes => ( {
	editMode: false,
	imageType: 'reviewer',
	orderby: 'most-recent',
	previewReviews: null as never,
	reviewsOnLoadMore: 2,
	reviewsOnPageLoad: 2,
	showLoadMore: true,
	showOrderby: true,
	showReviewContent: true,
	showReviewDate: false,
	showReviewerName: false,
	showReviewImage: false,
	showReviewRating: false,
	...overrides,
} );

const createFrontendElement = (
	className: string,
	dataAttributes: Record< string, unknown > = {}
) => {
	const element = document.createElement( 'div' );
	element.className = className;
	Object.entries( dataAttributes ).forEach( ( [ key, value ] ) => {
		if ( value !== undefined ) {
			element.setAttribute( key, String( value ) );
		}
	} );
	return element;
};

const getHydratedAttributes = ( element: HTMLElement ) => {
	if ( ! mockFrontendRegistration ) {
		throw new Error( 'Reviews frontend was not registered.' );
	}

	return {
		...element.dataset,
		...mockFrontendRegistration.getProps( element, 0 ).attributes,
	};
};

const reviews = [
	{ id: 1, review: 'First review' },
	{ id: 2, review: 'Second review' },
	{ id: 3, review: 'Third review' },
	{ id: 4, review: 'Fourth review' },
];

describe( 'Product Reviews contracts', () => {
	afterEach( () => {
		jest.clearAllMocks();
		mockGetSetting.mockImplementation(
			( setting, defaultValue ) => defaultValue
		);
	} );

	describe( 'sorting', () => {
		it.each( [
			[ 'most recent', 'most-recent', true, 'desc', 'date_gmt' ],
			[ 'highest rating', 'highest-rating', true, 'desc', 'rating' ],
			[ 'lowest rating', 'lowest-rating', true, 'asc', 'rating' ],
			[
				'ratings disabled fallback',
				'lowest-rating',
				false,
				'desc',
				'date_gmt',
			],
		] )(
			'maps %s to the Store API order',
			( _name, value, ratingsEnabled, order, orderby ) => {
				mockGetSetting.mockImplementation( ( setting, defaultValue ) =>
					setting === 'reviewRatingsEnabled'
						? ratingsEnabled
						: defaultValue
				);

				expect( getSortArgs( value ) ).toEqual( { order, orderby } );
			}
		);

		it( 'resets an appended list and requests the lowest ratings first', async () => {
			const user = userEvent.setup();
			mockGetReviews
				.mockResolvedValueOnce( {
					reviews: reviews.slice( 0, 2 ),
					totalReviews: 4,
				} )
				.mockResolvedValueOnce( {
					reviews: reviews.slice( 2 ),
					totalReviews: 4,
				} )
				.mockResolvedValueOnce( {
					reviews: reviews.slice( 0, 2 ),
					totalReviews: 4,
				} );

			render(
				<FrontendContainerBlock
					attributes={ createAttributes( { categoryIds: [ 7 ] } ) }
				/>
			);

			await waitFor( () =>
				expect( mockGetReviews ).toHaveBeenCalledTimes( 1 )
			);
			await user.click(
				await screen.findByRole( 'button', { name: /load more/i } )
			);
			await waitFor( () =>
				expect( mockGetReviews ).toHaveBeenCalledTimes( 2 )
			);
			expect( mockGetReviews ).toHaveBeenNthCalledWith( 2, {
				category_id: '7',
				offset: 2,
				order: 'desc',
				orderby: 'date_gmt',
				per_page: 2,
			} );

			await user.selectOptions(
				screen.getByRole( 'combobox', { name: 'Order reviews by' } ),
				'lowest-rating'
			);
			await waitFor( () =>
				expect( mockGetReviews ).toHaveBeenCalledTimes( 3 )
			);
			expect( mockGetReviews ).toHaveBeenNthCalledWith( 3, {
				category_id: '7',
				offset: 0,
				order: 'asc',
				orderby: 'rating',
				per_page: 2,
			} );
		} );
	} );

	describe( 'serialization and hydration', () => {
		it.each( [
			[
				'category IDs',
				{ categoryIds: [ 12, 34 ] },
				{ 'data-category-ids': '12,34' },
			],
			[ 'product ID', { productId: 56 }, { 'data-product-id': 56 } ],
			[
				'empty category IDs',
				{ categoryIds: [] },
				{ 'data-category-ids': '' },
			],
		] )( 'serializes %s', ( _name, selection, expected ) => {
			expect(
				getDataAttrs( createAttributes( selection ) )
			).toMatchObject( expected );
		} );

		it.each( [
			[ 'positive integer', 4, 4 ],
			[ 'numeric positive integer', '6', 6 ],
			[ 'zero', 0, undefined ],
			[ 'negative integer', -1, undefined ],
			[ 'fraction', 1.5, undefined ],
			[ 'non-numeric', 'invalid', undefined ],
		] )( 'serializes a %s offset', ( _name, offset, expected ) => {
			expect(
				getDataAttrs( createAttributes( { offset } ) )[ 'data-offset' ]
			).toBe( expected );
		} );

		it.each( [
			[
				'category block',
				'wp-block-woocommerce-reviews-by-category has-content',
				{ 'data-category-ids': '7', 'data-offset': '3' },
				{ categoryIds: '7', isFilteredReviewsBlock: true, offset: 3 },
			],
			[
				'product block',
				'wp-block-woocommerce-reviews-by-product',
				{ 'data-product-id': '9', 'data-offset': '-1' },
				{ productId: '9', isFilteredReviewsBlock: true, offset: 0 },
			],
			[
				'all reviews block',
				'wp-block-woocommerce-all-reviews',
				{ 'data-offset': '1.5' },
				{ isFilteredReviewsBlock: false, offset: 0 },
			],
		] )(
			'hydrates the %s identity and offset',
			( _name, className, dataAttributes, expected ) => {
				const element = createFrontendElement(
					className,
					dataAttributes
				);

				expect( getHydratedAttributes( element ) ).toMatchObject(
					expected
				);
			}
		);

		it.each( [
			'wp-block-woocommerce-reviews-by-category',
			'wp-block-woocommerce-reviews-by-product',
		] )( 'does not request reviews for an empty %s', ( className ) => {
			mockGetReviews.mockResolvedValue( {
				reviews: [],
				totalReviews: 0,
			} );
			const element = createFrontendElement( className );
			const { container } = render(
				<FrontendContainerBlock
					attributes={ createAttributes(
						getHydratedAttributes(
							element
						) as Partial< ReviewBlockAttributes >
					) }
				/>
			);

			expect( container ).toBeEmptyDOMElement();
			expect( mockGetReviews ).not.toHaveBeenCalled();
		} );

		it( 'does not request reviews for a saved empty category selection', () => {
			mockGetReviews.mockResolvedValue( {
				reviews: [],
				totalReviews: 0,
			} );
			const attributes = createAttributes( { categoryIds: [] } );
			const element = createFrontendElement(
				'wp-block-woocommerce-reviews-by-category',
				getDataAttrs( attributes )
			);
			const { container } = render(
				<FrontendContainerBlock
					attributes={ createAttributes(
						getHydratedAttributes(
							element
						) as Partial< ReviewBlockAttributes >
					) }
				/>
			);

			expect( container ).toBeEmptyDOMElement();
			expect( mockGetReviews ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'Offset control', () => {
		it( 'renders a zero default offset', () => {
			render(
				<>
					{ getSharedReviewListControls(
						createAttributes(),
						jest.fn(),
						{ showOffset: true }
					) }
				</>
			);

			expect(
				screen.getByRole( 'spinbutton', { name: 'Offset' } )
			).toHaveValue( 0 );
		} );

		it.each( [
			[ 'zero', '0', { offset: 0 } ],
			[ 'a positive integer', '8', { offset: 8 } ],
			[ 'negative zero', '-0', { offset: 0 } ],
			[ 'an empty value', '', undefined ],
			[ 'a fractional value', '1.5', undefined ],
			[ 'a negative integer', '-2', undefined ],
		] )( 'accepts or rejects %s', ( _name, value, expected ) => {
			const setAttributes = jest.fn();
			render(
				<>
					{ getSharedReviewListControls(
						createAttributes( { offset: 1 } ),
						setAttributes,
						{ showOffset: true }
					) }
				</>
			);

			fireEvent.change(
				screen.getByRole( 'spinbutton', { name: 'Offset' } ),
				{ target: { value } }
			);

			expect( setAttributes.mock.calls ).toEqual(
				expected ? [ [ expected ] ] : []
			);
		} );

		it( 'flows a positive value through serialization, hydration, and the review request', async () => {
			const setAttributes = jest.fn();
			const attributes = createAttributes( { categoryIds: [ 9 ] } );
			const controls = render(
				<>
					{ getSharedReviewListControls( attributes, setAttributes, {
						showOffset: true,
					} ) }
				</>
			);

			fireEvent.change(
				screen.getByRole( 'spinbutton', { name: 'Offset' } ),
				{ target: { value: '5' } }
			);
			expect( setAttributes ).toHaveBeenCalledWith( { offset: 5 } );
			controls.unmount();

			const serialized = getDataAttrs( { ...attributes, offset: 5 } );
			const element = createFrontendElement(
				'wp-block-woocommerce-reviews-by-category',
				serialized
			);
			mockGetReviews.mockResolvedValue( {
				reviews: reviews.slice( 0, 2 ),
				totalReviews: 4,
			} );

			render(
				<FrontendContainerBlock
					attributes={
						getHydratedAttributes(
							element
						) as ReviewBlockAttributes
					}
				/>
			);

			await waitFor( () =>
				expect( mockGetReviews ).toHaveBeenCalledWith( {
					category_id: 9,
					offset: 5,
					order: 'desc',
					orderby: 'date_gmt',
					per_page: 2,
				} )
			);
		} );
	} );
} );

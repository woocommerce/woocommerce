/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useBlockProps } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import {
	isExperimentalBlocksEnabled,
	isExperimentalWcRestApiEnabled,
} from '@woocommerce/block-settings';
import { useProduct } from '@woocommerce/entities';

/**
 * Internal dependencies
 */
import PriceEdit from './edit';
import Block from './block';
import { useIsDescendentOfSingleProductTemplate } from '../shared/use-is-descendent-of-single-product-template';

// Mock external dependencies
jest.mock( '@wordpress/block-editor', () => ( {
	AlignmentToolbar: ( { value, onChange } ) => (
		<div data-testid="alignment-toolbar">
			<button
				onClick={ () => onChange( 'left' ) }
				data-testid="align-left"
			>
				Left
			</button>
			<button
				onClick={ () => onChange( 'center' ) }
				data-testid="align-center"
			>
				Center
			</button>
			<button
				onClick={ () => onChange( 'right' ) }
				data-testid="align-right"
			>
				Right
			</button>
			<span data-testid="current-alignment">{ value }</span>
		</div>
	),
	BlockControls: ( { children } ) => (
		<div data-testid="block-controls">{ children }</div>
	),
	useBlockProps: jest.fn(),
} ) );

jest.mock( '@wordpress/element', () => ( {
	useEffect: jest.fn(),
} ) );

jest.mock( '@woocommerce/block-settings', () => ( {
	isExperimentalBlocksEnabled: jest.fn(),
	isExperimentalWcRestApiEnabled: jest.fn(),
} ) );

jest.mock( '@woocommerce/entities', () => ( {
	useProduct: jest.fn(),
} ) );

jest.mock( './block', () => jest.fn( () => <div data-testid="price-block" /> ) );

jest.mock( '../shared/use-is-descendent-of-single-product-template', () => ( {
	useIsDescendentOfSingleProductTemplate: jest.fn(),
} ) );

describe( 'PriceEdit Component', () => {
	// Mock functions
	const mockSetAttributes = jest.fn();
	const mockUseBlockProps = useBlockProps as jest.MockedFunction< typeof useBlockProps >;
	const mockUseEffect = useEffect as jest.MockedFunction< typeof useEffect >;
	const mockIsExperimentalBlocksEnabled = isExperimentalBlocksEnabled as jest.MockedFunction< typeof isExperimentalBlocksEnabled >;
	const mockIsExperimentalWcRestApiEnabled = isExperimentalWcRestApiEnabled as jest.MockedFunction< typeof isExperimentalWcRestApiEnabled >;
	const mockUseProduct = useProduct as jest.MockedFunction< typeof useProduct >;
	const mockUseIsDescendentOfSingleProductTemplate = useIsDescendentOfSingleProductTemplate as jest.MockedFunction< typeof useIsDescendentOfSingleProductTemplate >;
	const mockBlock = Block as jest.MockedFunction< typeof Block >;

	const defaultAttributes = {
		textAlign: 'left' as const,
		isDescendentOfSingleProduct: false,
		isDescendentOfSingleProductBlock: false,
		productId: 123,
	};

	const defaultContext = {
		queryId: 456,
		postId: 789,
	};

	const defaultProps = {
		attributes: defaultAttributes,
		setAttributes: mockSetAttributes,
		context: defaultContext,
	};

	beforeEach( () => {
		jest.clearAllMocks();
		
		// Default mock implementations
		mockUseBlockProps.mockReturnValue( { className: 'wp-block-price' } );
		mockUseEffect.mockImplementation( ( callback ) => callback() );
		mockIsExperimentalBlocksEnabled.mockReturnValue( true );
		mockIsExperimentalWcRestApiEnabled.mockReturnValue( true );
		mockUseProduct.mockReturnValue( { 
			product: { 
				id: 123, 
				name: 'Test Product', 
				price: '$10.00' 
			} 
		} );
		mockUseIsDescendentOfSingleProductTemplate.mockReturnValue( {
			isDescendentOfSingleProductTemplate: false,
		} );
	} );

	describe( 'Rendering', () => {
		test( 'renders without crashing', () => {
			render( <PriceEdit { ...defaultProps } /> );
			
			expect( screen.getByTestId( 'block-controls' ) ).toBeInTheDocument();
			expect( screen.getByTestId( 'alignment-toolbar' ) ).toBeInTheDocument();
			expect( screen.getByTestId( 'price-block' ) ).toBeInTheDocument();
		} );

		test( 'applies block props to container div', () => {
			const customBlockProps = { className: 'custom-class', id: 'custom-id' };
			mockUseBlockProps.mockReturnValue( customBlockProps );

			render( <PriceEdit { ...defaultProps } /> );
			
			const container = screen.getByTestId( 'price-block' ).parentElement;
			expect( container ).toHaveClass( 'custom-class' );
			expect( container ).toHaveAttribute( 'id', 'custom-id' );
		} );

		test( 'renders AlignmentToolbar with correct current alignment', () => {
			const propsWithCenterAlign = {
				...defaultProps,
				attributes: { ...defaultAttributes, textAlign: 'center' as const },
			};

			render( <PriceEdit { ...propsWithCenterAlign } /> );
			
			expect( screen.getByTestId( 'current-alignment' ) ).toHaveTextContent( 'center' );
		} );
	} );

	describe( 'Context and Query Loop Detection', () => {
		test( 'detects when component is descendant of query loop with finite queryId', () => {
			const contextWithQueryId = { ...defaultContext, queryId: 123 };
			
			render( 
				<PriceEdit 
					{ ...defaultProps } 
					context={ contextWithQueryId } 
				/> 
			);

			expect( mockBlock ).toHaveBeenCalledWith(
				expect.objectContaining( {
					queryId: 123,
				} ),
				expect.anything()
			);
		} );

		test( 'handles undefined queryId correctly', () => {
			const contextWithoutQueryId = { ...defaultContext, queryId: undefined };
			
			render( 
				<PriceEdit 
					{ ...defaultProps } 
					context={ contextWithoutQueryId } 
				/> 
			);

			// Should not be considered descendant of query loop
			expect( mockUseIsDescendentOfSingleProductTemplate ).toHaveBeenCalledWith( {
				isDescendentOfQueryLoop: false,
			} );
		} );

		test( 'handles NaN queryId correctly', () => {
			const contextWithNaNQueryId = { ...defaultContext, queryId: NaN };
			
			render( 
				<PriceEdit 
					{ ...defaultProps } 
					context={ contextWithNaNQueryId } 
				/> 
			);

			expect( mockUseIsDescendentOfSingleProductTemplate ).toHaveBeenCalledWith( {
				isDescendentOfQueryLoop: false,
			} );
		} );

		test( 'handles Infinity queryId correctly', () => {
			const contextWithInfinityQueryId = { ...defaultContext, queryId: Infinity };
			
			render( 
				<PriceEdit 
					{ ...defaultProps } 
					context={ contextWithInfinityQueryId } 
				/> 
			);

			expect( mockUseIsDescendentOfSingleProductTemplate ).toHaveBeenCalledWith( {
				isDescendentOfQueryLoop: false,
			} );
		} );
	} );

	describe( 'Single Product Template Logic', () => {
		test( 'overrides single product template detection when inside query loop', () => {
			mockUseIsDescendentOfSingleProductTemplate.mockReturnValue( {
				isDescendentOfSingleProductTemplate: true,
			} );

			const contextWithQueryId = { ...defaultContext, queryId: 123 };
			
			render( 
				<PriceEdit 
					{ ...defaultProps } 
					context={ contextWithQueryId } 
				/> 
			);

			// Should call setAttributes with isDescendentOfSingleProductTemplate: false
			// even though hook returned true, because it's inside query loop
			expect( mockSetAttributes ).toHaveBeenCalledWith( 
				expect.objectContaining( {
					isDescendentOfSingleProductTemplate: false,
				} )
			);
		} );

		test( 'preserves single product template detection when not in query loop', () => {
			mockUseIsDescendentOfSingleProductTemplate.mockReturnValue( {
				isDescendentOfSingleProductTemplate: true,
			} );

			const contextWithoutQueryId = { ...defaultContext, queryId: undefined };
			
			render( 
				<PriceEdit 
					{ ...defaultProps } 
					context={ contextWithoutQueryId } 
				/> 
			);

			expect( mockSetAttributes ).toHaveBeenCalledWith( 
				expect.objectContaining( {
					isDescendentOfSingleProductTemplate: true,
				} )
			);
		} );
	} );

	describe( 'useEffect Hook', () => {
		test( 'calls setAttributes with correct values on mount', () => {
			render( <PriceEdit { ...defaultProps } /> );

			expect( mockSetAttributes ).toHaveBeenCalledWith( {
				isDescendentOfQueryLoop: true, // queryId: 456 is finite
				isDescendentOfSingleProductTemplate: false, // overridden by query loop
			} );
		} );

		test( 'useEffect is called with correct dependencies', () => {
			render( <PriceEdit { ...defaultProps } /> );

			expect( mockUseEffect ).toHaveBeenCalledWith(
				expect.any( Function ),
				[
					true, // isDescendentOfQueryLoop
					false, // isDescendentOfSingleProductTemplate (overridden)
					mockSetAttributes,
				]
			);
		} );

		test( 'handles changes in dependencies correctly', () => {
			const { rerender } = render( <PriceEdit { ...defaultProps } /> );

			// Change context to trigger different values
			const newContext = { ...defaultContext, queryId: undefined };
			mockUseIsDescendentOfSingleProductTemplate.mockReturnValue( {
				isDescendentOfSingleProductTemplate: true,
			} );

			rerender( 
				<PriceEdit 
					{ ...defaultProps } 
					context={ newContext } 
				/> 
			);

			// Should be called again with new values
			expect( mockSetAttributes ).toHaveBeenCalledWith( {
				isDescendentOfQueryLoop: false,
				isDescendentOfSingleProductTemplate: true,
			} );
		} );
	} );

	describe( 'Alignment Toolbar Interaction', () => {
		test( 'handles left alignment change', async () => {
			const user = userEvent.setup();
			
			render( <PriceEdit { ...defaultProps } /> );
			
			await user.click( screen.getByTestId( 'align-left' ) );
			
			expect( mockSetAttributes ).toHaveBeenCalledWith( { textAlign: 'left' } );
		} );

		test( 'handles center alignment change', async () => {
			const user = userEvent.setup();
			
			render( <PriceEdit { ...defaultProps } /> );
			
			await user.click( screen.getByTestId( 'align-center' ) );
			
			expect( mockSetAttributes ).toHaveBeenCalledWith( { textAlign: 'center' } );
		} );

		test( 'handles right alignment change', async () => {
			const user = userEvent.setup();
			
			render( <PriceEdit { ...defaultProps } /> );
			
			await user.click( screen.getByTestId( 'align-right' ) );
			
			expect( mockSetAttributes ).toHaveBeenCalledWith( { textAlign: 'right' } );
		} );
	} );

	describe( 'Block Component Props', () => {
		test( 'passes all attributes and context to Block component', () => {
			render( <PriceEdit { ...defaultProps } /> );

			expect( mockBlock ).toHaveBeenCalledWith(
				expect.objectContaining( {
					...defaultAttributes,
					...defaultContext,
					isAdmin: true,
					product: expect.any( Object ),
					areExperimentalFlagsEnabled: true,
				} ),
				expect.anything()
			);
		} );

		test( 'sets isAdmin to true', () => {
			render( <PriceEdit { ...defaultProps } /> );

			expect( mockBlock ).toHaveBeenCalledWith(
				expect.objectContaining( { isAdmin: true } ),
				expect.anything()
			);
		} );

		test( 'passes product from useProduct hook', () => {
			const mockProduct = { id: 999, name: 'Mock Product', price: '$25.00' };
			mockUseProduct.mockReturnValue( { product: mockProduct } );

			render( <PriceEdit { ...defaultProps } /> );

			expect( mockBlock ).toHaveBeenCalledWith(
				expect.objectContaining( { product: mockProduct } ),
				expect.anything()
			);
		} );

		test( 'calls useProduct with correct postId from context', () => {
			render( <PriceEdit { ...defaultProps } /> );

			expect( mockUseProduct ).toHaveBeenCalledWith( defaultContext.postId );
		} );
	} );

	describe( 'Experimental Flags', () => {
		test( 'enables experimental features when both flags are true', () => {
			mockIsExperimentalBlocksEnabled.mockReturnValue( true );
			mockIsExperimentalWcRestApiEnabled.mockReturnValue( true );

			render( <PriceEdit { ...defaultProps } /> );

			expect( mockBlock ).toHaveBeenCalledWith(
				expect.objectContaining( { areExperimentalFlagsEnabled: true } ),
				expect.anything()
			);
		} );

		test( 'disables experimental features when blocks flag is false', () => {
			mockIsExperimentalBlocksEnabled.mockReturnValue( false );
			mockIsExperimentalWcRestApiEnabled.mockReturnValue( true );

			render( <PriceEdit { ...defaultProps } /> );

			expect( mockBlock ).toHaveBeenCalledWith(
				expect.objectContaining( { areExperimentalFlagsEnabled: false } ),
				expect.anything()
			);
		} );

		test( 'disables experimental features when WC REST API flag is false', () => {
			mockIsExperimentalBlocksEnabled.mockReturnValue( true );
			mockIsExperimentalWcRestApiEnabled.mockReturnValue( false );

			render( <PriceEdit { ...defaultProps } /> );

			expect( mockBlock ).toHaveBeenCalledWith(
				expect.objectContaining( { areExperimentalFlagsEnabled: false } ),
				expect.anything()
			);
		} );

		test( 'disables experimental features when both flags are false', () => {
			mockIsExperimentalBlocksEnabled.mockReturnValue( false );
			mockIsExperimentalWcRestApiEnabled.mockReturnValue( false );

			render( <PriceEdit { ...defaultProps } /> );

			expect( mockBlock ).toHaveBeenCalledWith(
				expect.objectContaining( { areExperimentalFlagsEnabled: false } ),
				expect.anything()
			);
		} );
	} );

	describe( 'Edge Cases and Error Handling', () => {
		test( 'handles missing product gracefully', () => {
			mockUseProduct.mockReturnValue( { product: null } );

			render( <PriceEdit { ...defaultProps } /> );

			expect( mockBlock ).toHaveBeenCalledWith(
				expect.objectContaining( { product: null } ),
				expect.anything()
			);
		} );

		test( 'handles undefined product gracefully', () => {
			mockUseProduct.mockReturnValue( { product: undefined } );

			render( <PriceEdit { ...defaultProps } /> );

			expect( mockBlock ).toHaveBeenCalledWith(
				expect.objectContaining( { product: undefined } ),
				expect.anything()
			);
		} );

		test( 'handles missing postId in context', () => {
			const contextWithoutPostId = { queryId: 123 };
			
			render( 
				<PriceEdit 
					{ ...defaultProps } 
					context={ contextWithoutPostId } 
				/> 
			);

			expect( mockUseProduct ).toHaveBeenCalledWith( undefined );
		} );

		test( 'handles empty attributes object', () => {
			const propsWithEmptyAttributes = {
				...defaultProps,
				attributes: {} as any,
			};

			render( <PriceEdit { ...propsWithEmptyAttributes } /> );

			expect( screen.getByTestId( 'price-block' ) ).toBeInTheDocument();
		} );

		test( 'handles null setAttributes function', () => {
			const propsWithNullSetAttributes = {
				...defaultProps,
				setAttributes: null as any,
			};

			// Should not throw an error
			expect( () => {
				render( <PriceEdit { ...propsWithNullSetAttributes } /> );
			} ).not.toThrow();
		} );
	} );

	describe( 'Type Safety and Alignment Constraints', () => {
		test( 'alignment toolbar only accepts allowed alignments', async () => {
			const user = userEvent.setup();
			
			render( <PriceEdit { ...defaultProps } /> );
			
			// Test valid alignments
			await user.click( screen.getByTestId( 'align-left' ) );
			expect( mockSetAttributes ).toHaveBeenCalledWith( { textAlign: 'left' } );
			
			await user.click( screen.getByTestId( 'align-center' ) );
			expect( mockSetAttributes ).toHaveBeenCalledWith( { textAlign: 'center' } );
			
			await user.click( screen.getByTestId( 'align-right' ) );
			expect( mockSetAttributes ).toHaveBeenCalledWith( { textAlign: 'right' } );
		} );

		test( 'validates textAlign attribute values', () => {
			const validAlignments = [ 'left', 'center', 'right' ] as const;
			
			validAlignments.forEach( ( alignment ) => {
				const propsWithAlignment = {
					...defaultProps,
					attributes: { ...defaultAttributes, textAlign: alignment },
				};
				
				render( <PriceEdit { ...propsWithAlignment } /> );
				
				expect( screen.getByTestId( 'current-alignment' ) ).toHaveTextContent( alignment );
			} );
		} );
	} );

	describe( 'Component Integration', () => {
		test( 'integrates correctly with WordPress block editor', () => {
			render( <PriceEdit { ...defaultProps } /> );

			// Verify block props are used
			expect( mockUseBlockProps ).toHaveBeenCalled();
			
			// Verify block controls are rendered
			expect( screen.getByTestId( 'block-controls' ) ).toBeInTheDocument();
			expect( screen.getByTestId( 'alignment-toolbar' ) ).toBeInTheDocument();
		} );

		test( 'integrates correctly with WooCommerce entities', () => {
			render( <PriceEdit { ...defaultProps } /> );

			// Verify product hook is called
			expect( mockUseProduct ).toHaveBeenCalledWith( defaultContext.postId );
			
			// Verify single product template hook is called
			expect( mockUseIsDescendentOfSingleProductTemplate ).toHaveBeenCalled();
		} );

		test( 'integrates correctly with WooCommerce block settings', () => {
			render( <PriceEdit { ...defaultProps } /> );

			// Verify experimental flags are checked
			expect( mockIsExperimentalBlocksEnabled ).toHaveBeenCalled();
			expect( mockIsExperimentalWcRestApiEnabled ).toHaveBeenCalled();
		} );
	} );
} );
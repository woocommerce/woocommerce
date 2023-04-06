/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react-hooks';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { PRODUCT_TOUR_MODAL_HIDDEN, useProductTour } from '../use-product-tour';

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useDispatch: jest.fn().mockReturnValue( {} ),
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

const mockedUseDispatch = useDispatch as jest.Mock;
const mockedUseSelect = useSelect as jest.Mock;

describe( 'useProductTour', () => {
	it( 'should initially set the tour to hidden', () => {
		const { result } = renderHook( () => useProductTour() );
		expect( result.current.isTouring ).toBeFalsy();
	} );

	it( 'should update the tour state when starting the tour', () => {
		const updateOptions = jest.fn();
		mockedUseDispatch.mockImplementation( () => ( {
			updateOptions,
		} ) );
		const { result } = renderHook( () => useProductTour() );
		act( () => {
			result.current.startTour();
		} );
		expect( result.current.isTouring ).toBeTruthy();
	} );

	it( 'should dismiss the modal when starting the tour', () => {
		const updateOptions = jest.fn();
		mockedUseDispatch.mockImplementation( () => ( {
			updateOptions,
		} ) );
		const { result } = renderHook( () => useProductTour() );

		act( () => {
			result.current.startTour();
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			[ PRODUCT_TOUR_MODAL_HIDDEN ]: 'yes',
		} );
	} );

	it( 'should update the tour state when ending the tour', () => {
		const { result } = renderHook( () => useProductTour() );
		act( () => {
			result.current.startTour();
			result.current.endTour();
		} );
		expect( result.current.isTouring ).toBeFalsy();
	} );

	it( 'should return true when the modal is hidden', () => {
		mockedUseSelect.mockImplementation( () => ( {
			isModalHidden: true,
		} ) );
		const { result } = renderHook( () => useProductTour() );
		expect( result.current.isModalHidden ).toBeTruthy();
	} );

	it( 'should return false when the modal is hidden', () => {
		mockedUseSelect.mockImplementation( () => ( {
			isModalHidden: false,
		} ) );
		const { result } = renderHook( () => useProductTour() );
		expect( result.current.isModalHidden ).toBeFalsy();
	} );

	it( 'should dismiss the modal when manually called', () => {
		const updateOptions = jest.fn();
		mockedUseDispatch.mockImplementation( () => ( {
			updateOptions,
		} ) );
		const { result } = renderHook( () => useProductTour() );

		act( () => {
			result.current.dismissModal();
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			[ PRODUCT_TOUR_MODAL_HIDDEN ]: 'yes',
		} );
	} );
} );

import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';
import '../../test/__mocks__/setup-shared-mocks';

/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom/vitest';
import * as dataModule from '@wordpress/data';

/**
 * Internal dependencies
 */
import { CategorySection } from '../category-section';
import { PersonalizationTag } from '../../../store';
const updateBlockAttributes = vi.fn();
const useDispatchMock = dataModule.useDispatch as Mock;
const useSelectMock = dataModule.useSelect as Mock;
vi.mock( '@wordpress/components', () => {
	const mock = {
		Button: ( props: React.ComponentProps< 'button' > ) => (
			<button onClick={ props.onClick }>{ props.children }</button>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/block-editor', () => {
	const mock = {
		store: {},
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const setupUseSelectMock = (
	selectedBlockId = '123',
	selectedBlockName = 'core/paragraph'
) => {
	useSelectMock
		.mockImplementationOnce( ( selector ) =>
			selector( () => ( {
				getSelectedBlockClientId: () => selectedBlockId,
			} ) )
		)
		.mockImplementationOnce( ( selector ) =>
			selector( () => ( {
				getBlock: () => ( {
					name: selectedBlockName,
				} ),
			} ) )
		);
};
describe( 'CategorySection', () => {
	const mockTags: Record< string, PersonalizationTag[] > = {
		General: [
			{
				name: 'Customer Name',
				token: 'woocommerce/customer-name',
				valueToInsert: '[woocommerce/customer-name]',
				category: 'Customer',
				attributes: [],
				postTypes: [ 'woo_mail' ],
			},
			{
				name: 'Customer Email',
				token: 'woocommerce/customer-email',
				valueToInsert: '[woocommerce/customer-email]',
				category: 'Customer',
				attributes: [],
				postTypes: [ 'woo_mail' ],
			},
		],
		Link: [
			{
				name: 'Profile URL',
				token: 'woocommerce/profile-url',
				valueToInsert: '[woocommerce/profile-url]',
				category: 'Link',
				attributes: [],
				postTypes: [ 'woo_mail' ],
			},
		],
	};
	const onInsert = vi.fn();
	const closeCallback = vi.fn();
	const openLinkModal = vi.fn();
	beforeEach( () => {
		vi.clearAllMocks();
		useDispatchMock.mockReturnValue( {
			updateBlockAttributes,
		} );
	} );
	it( 'should render tags for all categories', () => {
		setupUseSelectMock();
		render(
			<CategorySection
				groupedTags={ mockTags }
				activeCategory={ null }
				onInsert={ onInsert }
				canInsertLink={ true }
				closeCallback={ closeCallback }
				openLinkModal={ openLinkModal }
			/>
		);
		expect( screen.getByText( 'Customer Name' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Customer Email' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Profile URL' ) ).toBeInTheDocument();
	} );
	it( 'should call onInsert when Insert is clicked', () => {
		setupUseSelectMock();
		render(
			<CategorySection
				groupedTags={ mockTags }
				activeCategory={ 'General' }
				onInsert={ onInsert }
				canInsertLink={ false }
				closeCallback={ closeCallback }
				openLinkModal={ openLinkModal }
			/>
		);
		fireEvent.click( screen.getAllByText( 'Insert' )[ 0 ] );
		expect( onInsert ).toHaveBeenCalledWith(
			'[woocommerce/customer-name]',
			false
		);
	} );
	it( 'should call updateBlockAttributes and close modal when Set as URL is clicked', () => {
		setupUseSelectMock( '123', 'core/button' );
		render(
			<CategorySection
				groupedTags={ mockTags }
				activeCategory={ 'Link' }
				onInsert={ onInsert }
				canInsertLink={ false }
				closeCallback={ closeCallback }
				openLinkModal={ openLinkModal }
			/>
		);
		fireEvent.click( screen.getByText( 'Set as URL' ) );
		expect( updateBlockAttributes ).toHaveBeenCalledWith( '123', {
			url: '[woocommerce/profile-url]',
		} );
		expect( closeCallback ).toHaveBeenCalled();
	} );
	it( 'should call openLinkModal when Insert as link is clicked for Link category', () => {
		setupUseSelectMock();
		render(
			<CategorySection
				groupedTags={ mockTags }
				activeCategory={ 'Link' }
				onInsert={ onInsert }
				canInsertLink={ true }
				closeCallback={ closeCallback }
				openLinkModal={ openLinkModal }
			/>
		);
		fireEvent.click( screen.getByText( 'Insert as link' ) );
		expect( closeCallback ).toHaveBeenCalled();
		expect( openLinkModal ).toHaveBeenCalledWith( mockTags.Link[ 0 ] );
	} );
} );

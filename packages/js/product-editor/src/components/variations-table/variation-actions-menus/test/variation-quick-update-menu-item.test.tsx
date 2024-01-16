/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import React, { createElement } from 'react';

/**
 * Internal dependencies
 */
import { getGroupName, getMenuItem } from '..';
import {
	MULTIPLE_UPDATE,
	SINGLE_UPDATE,
	VARIATION_ACTIONS_SLOT_NAME,
} from '../constants';

describe( 'getMenuItem', () => {
	let onClickMock: jest.Mock;
	beforeEach( () => {
		onClickMock = jest.fn();
	} );

	it( 'should render the menuItem content', () => {
		const { queryByText } = render(
			getMenuItem( <button>My button</button>, onClickMock )
		);
		expect( queryByText( 'My button' ) ).toBeInTheDocument();
	} );

	it( 'should call onClick when the menuItem is clicked', async () => {
		const { getByRole } = render(
			getMenuItem( <button>My button</button>, onClickMock )
		);
		await fireEvent.click( getByRole( 'button', { name: 'My button' } ) );
		expect( onClickMock ).toHaveBeenCalled();
	} );
} );

describe( 'getGroupName', () => {
	it( 'should get group name for pricing group as multiple and single update', () => {
		const group = 'pricing';
		const isMultipleSelection = true;
		const groupNameSingle = getGroupName( group, ! isMultipleSelection );
		const groupNameMultiple = getGroupName( group, isMultipleSelection );
		expect( groupNameSingle ).toBe(
			`${ VARIATION_ACTIONS_SLOT_NAME }_${ group }_${ SINGLE_UPDATE }`
		);
		expect( groupNameMultiple ).toBe(
			`${ VARIATION_ACTIONS_SLOT_NAME }_${ group }_${ MULTIPLE_UPDATE }`
		);
	} );
	it( 'should get the variation actions slot name when group is empty', () => {
		const group = '';
		const isMultipleSelection = true;
		const groupName = getGroupName( group, ! isMultipleSelection );
		expect( groupName ).toBe( VARIATION_ACTIONS_SLOT_NAME );
	} );
} );

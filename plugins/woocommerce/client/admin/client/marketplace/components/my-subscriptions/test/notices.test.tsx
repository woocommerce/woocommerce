/**
 * External dependencies
 */
import { act, render, screen, fireEvent } from '@testing-library/react';
import { dispatch } from '@wordpress/data';

jest.mock( '../../../utils/functions', () => ( {
	removeNotice: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import Notices from '../notices';
import { noticeStore } from '../../../contexts/notice-store';
import { NoticeAction, NoticeStatus } from '../../../contexts/types';

const showNotice = ( action: NoticeAction ) =>
	act( () => {
		void dispatch( noticeStore ).addNotice(
			'product-key',
			'Product Add-Ons couldn’t be updated.',
			NoticeStatus.Error,
			{ actions: [ action ] }
		);
	} );

describe( 'Notices', () => {
	afterEach( () => {
		act( () => {
			void dispatch( noticeStore ).removeNotice( 'product-key' );
		} );
	} );

	it( 'renders an onClick-only action as a button, even with an empty url', () => {
		const onClick = jest.fn();
		render( <Notices /> );
		showNotice( { label: 'Try again', onClick, url: '' } );

		fireEvent.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		expect( onClick ).toHaveBeenCalledTimes( 1 );
		expect( screen.queryByRole( 'link' ) ).not.toBeInTheDocument();
	} );

	it( 'renders a url action as a link in the same tab', () => {
		render( <Notices /> );
		showNotice( {
			label: 'Update WordPress',
			url: 'https://example.test/wp-admin/update-core.php',
		} );

		const link = screen.getByRole( 'link', { name: 'Update WordPress' } );
		expect( link ).toHaveAttribute(
			'href',
			'https://example.test/wp-admin/update-core.php'
		);
		expect( link ).not.toHaveAttribute( 'target' );
	} );

	it( 'renders an openInNewTab action as a link that opens in a new tab', () => {
		render( <Notices /> );
		showNotice( {
			label: 'Learn more',
			url: 'https://wordpress.org/support/update-php/',
			openInNewTab: true,
		} );

		const link = screen.getByRole( 'link', { name: /Learn more/ } );
		expect( link ).toHaveAttribute(
			'href',
			'https://wordpress.org/support/update-php/'
		);
		expect( link ).toHaveAttribute( 'target', '_blank' );
	} );
} );

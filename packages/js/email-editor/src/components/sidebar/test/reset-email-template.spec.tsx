/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import { parse, serialize } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import getResetEmailTemplateAction from '../reset-email-template';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '@wordpress/data', () => ( { useDispatch: jest.fn() } ) );
jest.mock( '@wordpress/notices', () => ( {
	store: { name: 'core/notices' },
} ) );
jest.mock( '@wordpress/core-data', () => ( { store: { name: 'core' } } ) );
jest.mock( '@wordpress/blocks', () => ( {
	parse: jest.fn( () => [ { name: 'core/paragraph' } ] ),
	serialize: jest.fn( () => '<!-- default -->' ),
} ) );
jest.mock( '@wordpress/components', () => ( {
	Button: ( { children, onClick, disabled } ) => (
		<button onClick={ onClick } disabled={ disabled }>
			{ children }
		</button>
	),
} ) );
jest.mock( '../../../events', () => ( { recordEvent: jest.fn() } ) );

const apiFetchMock = apiFetch as unknown as jest.Mock;

const template = {
	id: 'woocommerce//order-email',
	type: 'wp_template',
	source: 'custom',
	origin: 'plugin',
	plugin: 'woocommerce',
	has_theme_file: false,
	title: 'Order &amp; shipping',
	permissions: { delete: true },
};

describe( 'reset email template action', () => {
	const action = getResetEmailTemplateAction();

	it.each( [
		[ 'a customized plugin template', {}, true ],
		[
			'a customized theme template',
			{ plugin: '', has_theme_file: true },
			true,
		],
		[ 'a template that was never customized', { source: 'plugin' }, false ],
		[ 'a template only the user created', { plugin: '' }, false ],
		[ 'something other than a template', { type: 'woo_email' }, false ],
		[
			'a template the user may not delete',
			{ permissions: { delete: false } },
			false,
		],
	] )( 'offers reset for %s: %s', ( _case, changes, expected ) => {
		expect(
			Boolean(
				action.isEligible( {
					...template,
					...changes,
				} as never )
			)
		).toBe( expected );
	} );

	describe( 'confirming the reset', () => {
		// The click starts an async save, so wait for it inside act().
		const clickReset = async () => {
			const button = screen.getByRole( 'button', { name: 'Reset' } );
			await act( async () => {
				button.click();
			} );
		};

		const dispatch = {
			createSuccessNotice: jest.fn(),
			createErrorNotice: jest.fn(),
			invalidateResolution: jest.fn(),
			editEntityRecord: jest.fn(),
			saveEditedEntityRecord: jest.fn(),
		};
		const closeModal = jest.fn();
		const onActionPerformed = jest.fn();

		beforeEach( () => {
			jest.clearAllMocks();
			apiFetchMock.mockReset();
			( useDispatch as jest.Mock ).mockReturnValue( dispatch );
			const { RenderModal } = action;
			render(
				<RenderModal
					items={ [ template ] }
					closeModal={ closeModal }
					onActionPerformed={ onActionPerformed }
				/>
			);
		} );

		it( 'saves the file template content, deletes the custom copy and confirms', async () => {
			apiFetchMock.mockResolvedValueOnce( {
				content: { raw: '<!-- default -->' },
			} );
			apiFetchMock.mockResolvedValueOnce( {} );
			let finishSave: () => void;
			dispatch.saveEditedEntityRecord.mockReturnValueOnce(
				new Promise< void >( ( resolve ) => {
					finishSave = resolve;
				} )
			);

			await clickReset();

			// The custom copy may only be deleted once the save has finished.
			expect( dispatch.saveEditedEntityRecord ).toHaveBeenCalled();
			expect( apiFetchMock ).toHaveBeenCalledTimes( 1 );
			expect( dispatch.createSuccessNotice ).not.toHaveBeenCalled();
			await act( async () => finishSave() );

			await waitFor( () => expect( closeModal ).toHaveBeenCalled() );
			expect( apiFetchMock ).toHaveBeenNthCalledWith( 1, {
				path: '/wp/v2/templates/woocommerce//order-email?context=edit&source=plugin',
			} );
			expect( parse ).toHaveBeenCalledWith( '<!-- default -->' );
			expect( dispatch.saveEditedEntityRecord ).toHaveBeenCalledWith(
				'postType',
				'wp_template',
				template.id,
				{}
			);
			expect( dispatch.editEntityRecord ).toHaveBeenCalledWith(
				'postType',
				'wp_template',
				template.id,
				{
					blocks: [ { name: 'core/paragraph' } ],
					content: '<!-- default -->',
					source: 'plugin',
				}
			);
			expect( serialize ).toHaveBeenCalledWith( [
				{ name: 'core/paragraph' },
			] );
			expect( apiFetchMock ).toHaveBeenNthCalledWith( 2, {
				path: '/wp/v2/templates/woocommerce//order-email',
				method: 'DELETE',
			} );
			expect( dispatch.invalidateResolution ).toHaveBeenCalledWith(
				'getEntityRecord',
				[ 'postType', 'wp_template', template.id ]
			);
			expect( dispatch.createSuccessNotice ).toHaveBeenCalledWith(
				'"Order & shipping" reset to default.',
				expect.anything()
			);
			expect( onActionPerformed ).toHaveBeenCalledWith( [ template ] );
		} );

		it( 'shows the error and keeps the template when the reset fails', async () => {
			apiFetchMock.mockRejectedValueOnce( new Error( 'Server error.' ) );

			await clickReset();

			await waitFor( () => expect( closeModal ).toHaveBeenCalled() );
			expect( dispatch.createErrorNotice ).toHaveBeenCalledWith(
				'Server error.',
				expect.anything()
			);
			expect( dispatch.saveEditedEntityRecord ).not.toHaveBeenCalled();
			expect( dispatch.createSuccessNotice ).not.toHaveBeenCalled();
			expect( onActionPerformed ).not.toHaveBeenCalled();
		} );
	} );
} );

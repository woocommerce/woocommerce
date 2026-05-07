/* eslint-disable @woocommerce/dependency-group -- because we import mocks first, we deactivate this rule to avoid ESLint errors */
import '../../test/__mocks__/setup-shared-mocks';

/**
 * External dependencies
 */
import { fireEvent, render, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { TemplateCanvasAffordance } from '../template-canvas-affordance';
import { storeName } from '../../../store';
import { recordEvent } from '../../../events';

jest.mock( '@wordpress/components', () => ( {
	Button: ( { children, className, onClick, variant } ) => (
		<button
			className={ `${ className } components-button is-${ variant }` }
			onClick={ onClick }
		>
			{ children }
		</button>
	),
} ) );

jest.mock( '@wordpress/icons', () => ( {
	Icon: () => <span data-testid="template-area-icon" />,
	layout: 'layout',
} ) );

jest.mock( '../../../events', () => ( {
	recordEvent: jest.fn(),
} ) );

const useSelectMock = useSelect as jest.Mock;
const recordEventMock = recordEvent as jest.Mock;

const template = {
	id: 'twentytwentyfive//wooemailtemplate',
	title: 'Woo email template',
};

const setupUseSelectMock = ( {
	canEditTemplates = true,
	currentPostType = 'woo_email',
	onNavigateToEntityRecord = jest.fn(),
} = {} ) => {
	useSelectMock.mockImplementation( ( selector ) =>
		selector( ( store ) => {
			if ( store === storeName ) {
				return {
					canUserEditTemplates: () => canEditTemplates,
					getCurrentTemplate: () => template,
				};
			}

			if ( store === editorStore ) {
				return {
					getCurrentPostType: () => currentPostType,
					getEditorSettings: () => ( {
						onNavigateToEntityRecord,
					} ),
				};
			}

			return {};
		} )
	);

	return { onNavigateToEntityRecord };
};

const addEditorCanvas = () => {
	const iframe = document.createElement( 'iframe' );
	document.body.appendChild( iframe );
	iframe.contentDocument?.body.insertAdjacentHTML(
		'beforeend',
		'<div><div class="block-editor-block-list__layout is-root-container"></div></div>'
	);

	return iframe;
};

describe( 'TemplateCanvasAffordance', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		document.body.innerHTML = '';
	} );

	it( 'renders an edit template affordance before the editor canvas content', async () => {
		const iframe = addEditorCanvas();
		setupUseSelectMock();

		render( <TemplateCanvasAffordance /> );

		await waitFor( () => {
			expect(
				iframe.contentDocument?.getElementById(
					'woocommerce-email-editor-template-area-affordance-slot'
				)
			).toBeInTheDocument();
		} );

		expect( iframe.contentDocument?.body ).toHaveTextContent( 'Template' );
		expect( iframe.contentDocument?.body ).toHaveTextContent(
			'Edit template'
		);
	} );

	it( 'navigates to the current template when edit template is clicked', async () => {
		const iframe = addEditorCanvas();
		const { onNavigateToEntityRecord } = setupUseSelectMock();

		render( <TemplateCanvasAffordance /> );

		await waitFor( () => {
			expect(
				iframe.contentDocument?.querySelector( 'button' )
			).toBeInTheDocument();
		} );

		fireEvent.click(
			iframe.contentDocument?.querySelector(
				'button'
			) as HTMLButtonElement
		);

		expect( recordEventMock ).toHaveBeenCalledWith(
			'template_canvas_affordance_edit_template_clicked',
			{ templateId: template.id }
		);
		expect( onNavigateToEntityRecord ).toHaveBeenCalledWith( {
			postId: template.id,
			postType: 'wp_template',
		} );
	} );

	it( 'does not render while editing a template', async () => {
		const iframe = addEditorCanvas();
		setupUseSelectMock( { currentPostType: 'wp_template' } );

		render( <TemplateCanvasAffordance /> );

		await waitFor( () => {
			expect(
				iframe.contentDocument?.getElementById(
					'woocommerce-email-editor-template-area-affordance-slot'
				)
			).not.toBeInTheDocument();
		} );
	} );
} );

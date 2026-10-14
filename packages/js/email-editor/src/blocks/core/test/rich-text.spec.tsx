import '../../../components/test/__mocks__/setup-shared-mocks';

/**
 * External dependencies
 */
import { registerFormatType } from '@wordpress/rich-text';

/**
 * Internal dependencies
 */
import { extendRichTextFormats } from '../rich-text';

jest.mock( '@wordpress/rich-text', () => ( {
	registerFormatType: jest.fn(),
	unregisterFormatType: jest.fn(),
} ) );

jest.mock( '@wordpress/components', () => ( {
	ToolbarButton: () => null,
	ToolbarGroup: () => null,
} ) );

jest.mock( '../../../store', () => ( {
	storeName: 'email-editor',
} ) );

jest.mock( '../../../events', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock(
	'../../../components/personalization-tags/personalization-tags-modal',
	() => ( {
		PersonalizationTagsModal: () => null,
	} )
);

jest.mock(
	'../../../components/personalization-tags/personalization-tags-popover',
	() => ( {
		PersonalizationTagsPopover: () => null,
	} )
);

jest.mock(
	'../../../components/personalization-tags/personalization-tags-link-popover',
	() => ( {
		PersonalizationTagsLinkPopover: () => null,
	} )
);

const registerFormatTypeMock = registerFormatType as jest.Mock;

describe( 'extendRichTextFormats', () => {
	beforeEach( () => {
		registerFormatTypeMock.mockClear();
		extendRichTextFormats();
	} );

	it( 'registers the personalization tags format as non-interactive', () => {
		// Blocks using `withoutInteractiveFormatting` (e.g. the Button block)
		// drop interactive formats entirely, which would hide the
		// Personalization Tags toolbar button there.
		expect( registerFormatTypeMock ).toHaveBeenCalledWith(
			'woocommerce-email-editor/shortcode',
			expect.objectContaining( {
				interactive: false,
				edit: expect.any( Function ),
			} )
		);
	} );

	it( 'registers the link format as interactive', () => {
		// The link format renders a real `a` element and is applied
		// programmatically via `applyFormat`, so unlike the shortcode format it
		// has no toolbar `edit` component that the interactive flag could hide.
		expect( registerFormatTypeMock ).toHaveBeenCalledWith(
			'woocommerce-email-editor/link-shortcode',
			expect.objectContaining( { interactive: true, edit: null } )
		);
	} );
} );

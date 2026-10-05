import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';
import '../../../components/test/__mocks__/setup-shared-mocks';

/**
 * External dependencies
 */
import { registerFormatType } from '@wordpress/rich-text';

/**
 * Internal dependencies
 */
import { extendRichTextFormats } from '../rich-text';
vi.mock( '@wordpress/rich-text', () => {
	const mock = {
		registerFormatType: vi.fn(),
		unregisterFormatType: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/components', () => {
	const mock = {
		ToolbarButton: () => null,
		ToolbarGroup: () => null,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../../store', () => {
	const mock = {
		storeName: 'email-editor',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../../events', () => {
	const mock = {
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock(
	'../../../components/personalization-tags/personalization-tags-modal',
	() => {
		const mock = {
			PersonalizationTagsModal: () => null,
		};
		return Object.defineProperties(
			{
				default: mock,
			},
			Object.getOwnPropertyDescriptors( mock )
		);
	}
);
vi.mock(
	'../../../components/personalization-tags/personalization-tags-popover',
	() => {
		const mock = {
			PersonalizationTagsPopover: () => null,
		};
		return Object.defineProperties(
			{
				default: mock,
			},
			Object.getOwnPropertyDescriptors( mock )
		);
	}
);
vi.mock(
	'../../../components/personalization-tags/personalization-tags-link-popover',
	() => {
		const mock = {
			PersonalizationTagsLinkPopover: () => null,
		};
		return Object.defineProperties(
			{
				default: mock,
			},
			Object.getOwnPropertyDescriptors( mock )
		);
	}
);
const registerFormatTypeMock = registerFormatType as Mock;
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
			expect.objectContaining( {
				interactive: true,
				edit: null,
			} )
		);
	} );
} );

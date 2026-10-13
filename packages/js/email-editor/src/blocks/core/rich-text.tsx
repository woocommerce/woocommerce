/**
 * External dependencies
 */
import { applyFormat } from '@wordpress/rich-text';
import type {
	RichTextValue,
	RichTextFormat as WPFormat,
} from '@wordpress/rich-text/build-types/types';
import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useCallback, useState } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import * as React from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	getElementRange,
	insertPersonalizationTag,
	replacePersonalizationTagsWithHTMLComments,
} from '../../components/personalization-tags/rich-text-utils';
import { PersonalizationTagsModal } from '../../components/personalization-tags/personalization-tags-modal';
import { storeName } from '../../store';
import { PersonalizationTagsPopover } from '../../components/personalization-tags/personalization-tags-popover';
import { PersonalizationTagsLinkPopover } from '../../components/personalization-tags/personalization-tags-link-popover';
import { recordEvent } from '../../events';
import {
	registerFormatForEmail,
	unregisterFormatForEmail,
} from '../../config-tools/formats';
import { addFilterForEmail } from '../../config-tools/filters';

/**
 * Disable Rich text formats we currently cannot support
 * Note: This will remove its support for all blocks in the email editor e.g., p, h1,h2, etc
 */
function disableCertainRichTextFormats() {
	// Format types to disable
	const formatTypesToDisable = [
		'core/image', // remove support for inline image - We can't use it
		'core/code', // remove support for Inline code - Not well formatted
		'core/language', // remove support for Language - Not supported for now
	];

	// Unregister each format type and preserve its definition
	formatTypesToDisable.forEach( ( formatName ) => {
		unregisterFormatForEmail( formatName );
	} );
}

const OBJECT_REPLACEMENT_CHARACTER = '\ufffc';

// The bundled RichText types omit these properties.
type LooseFormat = {
	type: string;
	attributes?: Record< string, string >;
	unregisteredAttributes?: Record< string, string >;
};

type Props = {
	contentRef: React.RefObject< HTMLElement >;
	value: RichTextValue;
	onChange: ( value: RichTextValue ) => void;
};

/**
 * A button to the rich text editor to open modal with registered personalization tags.
 *
 * @param root0
 * @param root0.contentRef
 * @param root0.value
 * @param root0.onChange
 */
function PersonalizationTagsButton( { contentRef, value, onChange }: Props ) {
	const [ isModalOpened, setIsModalOpened ] = useState( false );
	const handleInsert = useCallback(
		( tag: string, linkText: string | null ) =>
			onChange( insertPersonalizationTag( value, tag, linkText ) ),
		[ value, onChange ]
	);

	return (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarButton
					icon="shortcode"
					title={ __( 'Personalization Tags', __i18n_text_domain__ ) }
					onClick={ () => {
						setIsModalOpened( true );
						recordEvent(
							'block_controls_personalization_tags_button_clicked'
						);
					} }
				/>
				<PersonalizationTagsPopover
					contentRef={ contentRef }
					onUpdate={ ( _originalTag, updatedTag, anchor ) => {
						const range =
							anchor &&
							getElementRange( contentRef.current, anchor );
						if (
							! range ||
							range.end - range.start !== 1 ||
							value.text[ range.start ] !==
								OBJECT_REPLACEMENT_CHARACTER ||
							value.replacements[ range.start ]?.type !==
								'#comment'
						) {
							return;
						}
						const replacements = (
							value.replacements as LooseFormat[]
						 ).slice();
						const replacement = replacements[ range.start ];
						replacements[ range.start ] = {
							...replacement,
							attributes: {
								...replacement.attributes,
								// The popover removes the brackets, so add them back.
								'data-rich-text-comment': `[${ updatedTag }]`,
							},
						};
						onChange( {
							...value,
							replacements,
							activeFormats: [],
						} as RichTextValue );
					} }
				/>
				<PersonalizationTagsLinkPopover
					contentRef={ contentRef }
					onUpdate={ ( linkElement, newTag, newText ) => {
						const range = getElementRange(
							contentRef.current,
							linkElement,
							true
						);
						const formats = ( ( range &&
							value.formats[ range.start ] ) ||
							[] ) as LooseFormat[];
						const isLinkFormat = ( format: LooseFormat ) =>
							format.attributes?.[ 'data-link-href' ] !==
								undefined ||
							format.unregisteredAttributes?.[
								'data-link-href'
							] !== undefined;
						if ( ! range || ! formats.some( isLinkFormat ) ) {
							return;
						}
						// Never turn the link into a plain tag: empty text keeps the old text.
						const text = newText.trim()
							? newText
							: value.text.slice( range.start, range.end );
						let result = insertPersonalizationTag(
							{
								...value,
								start: range.start,
								end: range.end,
							},
							newTag,
							text
						);
						// Re-apply the formatting that surrounded the old link.
						formats
							.filter( ( format ) => ! isLinkFormat( format ) )
							.forEach( ( format ) => {
								result = {
									...applyFormat(
										result,
										format as unknown as WPFormat,
										range.start,
										range.start + text.length
									),
									activeFormats: [],
								} as RichTextValue;
							} );
						onChange( result );
					} }
				/>
				<PersonalizationTagsModal
					isOpened={ isModalOpened }
					onInsert={ ( tag, linkText ) => {
						handleInsert( tag, linkText );
						setIsModalOpened( false );
					} }
					closeCallback={ () => setIsModalOpened( false ) }
					canInsertLink
					openedBy="block-controls"
				/>
			</ToolbarGroup>
		</BlockControls>
	);
}

/**
 * Extend the rich text formats with a button for personalization tags.
 */
function extendRichTextFormats() {
	registerFormatForEmail( 'woocommerce-email-editor/shortcode', {
		name: 'woocommerce-email-editor/shortcode',
		title: __( 'Personalization Tags', __i18n_text_domain__ ),
		className: 'woocommerce-email-editor-personalization-tags',
		tagName: 'span',
		attributes: {},
		// Must stay non-interactive: blocks using `withoutInteractiveFormatting`
		// (e.g. the Button block) drop interactive formats entirely, which would
		// hide the Personalization Tags toolbar button there.
		interactive: false,
		edit: PersonalizationTagsButton,
	} );

	// Register format type for using personalization tags as link attributes
	registerFormatForEmail( 'woocommerce-email-editor/link-shortcode', {
		name: 'woocommerce-email-editor/link-shortcode',
		title: __( 'Personalization Tags Link', __i18n_text_domain__ ),
		className: 'woocommerce-email-editor-personalization-tags-link',
		tagName: 'a',
		attributes: {
			'data-link-href': 'data-link-href',
			contenteditable: 'contenteditable',
			style: 'style',
		},
		interactive: true,
		edit: null,
	} );
}

const personalizationTagsLiveContentUpdate = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		const { attributes, setAttributes, name } = props;
		const { content } = attributes;

		// Fetch the personalization tags list
		const list = useSelect(
			( select ) => select( storeName ).getPersonalizationTagsList(),
			[]
		);

		// Memoized function to replace content tags
		const updateContent = useCallback( () => {
			if ( ! content ) {
				return '';
			}
			return replacePersonalizationTagsWithHTMLComments( content, list );
		}, [ content, list ] );

		// Handle content updates
		const handleSetAttributes = useCallback(
			( newAttributes ) => {
				if ( newAttributes.content !== undefined ) {
					const replacedContent =
						replacePersonalizationTagsWithHTMLComments(
							newAttributes.content,
							list
						);
					setAttributes( {
						...newAttributes,
						content: replacedContent,
					} );
				} else {
					setAttributes( newAttributes );
				}
			},
			[ list, setAttributes ]
		);

		// Only process supported blocks
		if (
			name === 'core/paragraph' ||
			name === 'core/heading' ||
			name === 'core/list-item'
		) {
			return (
				<BlockEdit
					{ ...props }
					attributes={ {
						...attributes,
						content: updateContent(),
					} }
					setAttributes={ handleSetAttributes }
				/>
			);
		}

		// Return default for unsupported blocks
		return <BlockEdit { ...props } />;
	},
	'personalizationTagsLiveContentUpdate'
);

/**
 * Replace written personalization tags with HTML comments in real-time.
 */
function activatePersonalizationTagsReplacing() {
	addFilterForEmail(
		'editor.BlockEdit',
		'woocommerce-email-editor/with-live-content-update',
		personalizationTagsLiveContentUpdate
	);
}

export {
	disableCertainRichTextFormats,
	extendRichTextFormats,
	activatePersonalizationTagsReplacing,
};

/**
 * External dependencies
 */
import type { ReactNode } from 'react';
import { BaseControl, Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { create, insert, toHTMLString } from '@wordpress/rich-text';
import { RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PersonalizationTagsModal } from './personalization-tags-modal';
import {
	getCursorPosition,
	getElementRange,
	renameTagComment,
	replacePersonalizationTagsWithHTMLComments,
} from './rich-text-utils';
import { storeName } from '../../store';
import { PersonalizationTagsPopover } from './personalization-tags-popover';
import { recordEvent, recordEventOnce } from '../../events';

type Props = {
	label: string;
	labelSuffix?: ReactNode;
	help?: ReactNode;
	placeholder: string;
	attributeName: string;
	attributeValue?: string;
	updateProperty: (
		theAttributeName: string,
		theUpdatedValue: string
	) => void;
};

export function RichTextWithButton( {
	label,
	labelSuffix,
	help,
	placeholder,
	attributeName,
	attributeValue,
	updateProperty = () => {},
}: Props ) {
	const selectionRange = useRef< { start: number; end: number } | null >(
		null
	);
	const [ isModalOpened, setIsModalOpened ] = useState( false );
	const list = useSelect(
		( select ) => select( storeName ).getPersonalizationTagsList(),
		[]
	);

	const richTextRef = useRef( null );

	useEffect( () => {
		const doc = richTextRef.current?.ownerDocument ?? document;
		const onSelectionChange = () => {
			if (
				! richTextRef.current ||
				doc.activeElement !== richTextRef.current
			) {
				return;
			}
			const position = getCursorPosition( richTextRef );
			if ( position ) {
				selectionRange.current = position;
			}
		};
		doc.addEventListener( 'selectionchange', onSelectionChange );
		return () =>
			doc.removeEventListener( 'selectionchange', onSelectionChange );
	}, [] );

	const handleInsertPersonalizationTag = useCallback(
		( tagName, currentValue, currentSelectionRange ) => {
			let richTextValue = create( { html: currentValue } );
			const start =
				currentSelectionRange?.start ?? richTextValue.text.length;
			const end = currentSelectionRange?.end ?? start;

			richTextValue = insert(
				richTextValue,
				create( { html: `<!--${ tagName }-->` } ),
				start,
				end
			);
			const updatedValue = toHTMLString( { value: richTextValue } );

			// Update the corresponding property
			updateProperty( attributeName, updatedValue );

			selectionRange.current = { start: start + 1, end: start + 1 };
		},
		[ attributeName, updateProperty ]
	);

	const finalLabel = (
		<>
			<span>{ label }</span>
			<Button
				className="woocommerce-settings-panel-personalization-tags-button"
				icon="shortcode"
				title={ __( 'Personalization Tags', __i18n_text_domain__ ) }
				onClick={ () => {
					setIsModalOpened( true );
					recordEvent(
						'rich_text_with_button_personalization_tags_shortcode_icon_clicked',
						{
							attributeName,
							label,
						}
					);
				} }
			/>
			{ labelSuffix }
		</>
	);

	if ( ! attributeName ) {
		return null;
	}

	return (
		<BaseControl
			id="" // See https://github.com/mailpoet/mailpoet/pull/6089#discussion_r1952126850 to understand why the ID is empty
			label={ finalLabel }
			className={ `woocommerce-settings-panel-${ attributeName }-text` }
			help={ help }
			__nextHasNoMarginBottom // To avoid warning about deprecation in console
		>
			<PersonalizationTagsModal
				isOpened={ isModalOpened }
				onInsert={ ( value ) => {
					handleInsertPersonalizationTag(
						value,
						attributeValue ?? '',
						selectionRange.current
					);
					setIsModalOpened( false );
					recordEvent(
						'rich_text_with_button_personalization_tags_inserted',
						{
							attributeName,
							value,
						}
					);
				} }
				closeCallback={ () => setIsModalOpened( false ) }
				openedBy="RichTextWithButton-BaseControl"
			/>
			<PersonalizationTagsPopover
				contentRef={ richTextRef }
				onUpdate={ ( originalTag, updatedTag, anchor ) => {
					const currentValue = attributeValue ?? '';
					if ( anchor ) {
						const updated = renameTagComment(
							create( { html: currentValue } ),
							getElementRange( richTextRef.current, anchor ),
							updatedTag
						);
						if ( updated ) {
							updateProperty(
								attributeName,
								toHTMLString( { value: updated } )
							);
							return;
						}
					}
					// When we update the tag, we need to add brackets to the tag, because the popover removes them
					updateProperty(
						attributeName,
						currentValue.replace(
							`<!--[${ originalTag }]-->`,
							`<!--[${ updatedTag }]-->`
						)
					);
				} }
			/>
			<RichText
				ref={ richTextRef }
				className="woocommerce-settings-panel-richtext"
				placeholder={ placeholder }
				onChange={ ( value ) => {
					value = replacePersonalizationTagsWithHTMLComments(
						value ?? '',
						list
					);
					updateProperty( attributeName, value );
					recordEventOnce(
						'rich_text_with_button_input_field_updated',
						{
							attributeName,
						}
					);
				} }
				value={ attributeValue ?? '' }
				data-automation-id={ `email_${ attributeName }` }
			/>
		</BaseControl>
	);
}

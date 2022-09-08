/**
 * External dependencies
 */
import { speak } from '@wordpress/a11y';
import { RichTextShortcut } from '@wordpress/block-editor';
import { createElement, Fragment, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { link as linkIcon, linkOff } from '@wordpress/icons';
import { isURL, isEmail } from '@wordpress/url';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	ExtendedFormatConfiguration,
	getTextContent,
	applyFormat,
	registerFormatType,
	removeFormat,
	slice,
	isCollapsed,
	Value,
} from '@wordpress/rich-text';

/**
 * Internal dependencies
 */
import InlineLinkUI from './inline';
import { isValidHref } from './utils';
import { EditProps } from '../types';
import { FormatToolbarButton } from '../../components/format-toolbar-button';
import { formatIsRegistered } from '../register-format-types';

const name = 'core/link';
const title = __( 'Link', 'woocommerce' );

function Edit( {
	isActive,
	activeAttributes,
	value,
	onChange,
	onFocus,
	contentRef,
}: EditProps ) {
	const [ addingLink, setAddingLink ] = useState( false );

	function addLink() {
		const text = getTextContent( slice( value ) );

		if ( text && isURL( text ) && isValidHref( text ) ) {
			onChange(
				applyFormat( value, {
					type: name,
					attributes: { url: text },
				} )
			);
		} else if ( text && isEmail( text ) ) {
			onChange(
				applyFormat( value, {
					type: name,
					attributes: { url: `mailto:${ text }` },
				} )
			);
		} else {
			setAddingLink( true );
		}
	}

	function stopAddingLink() {
		setAddingLink( false );
		onFocus();
	}

	function onRemoveFormat() {
		onChange( removeFormat( value, name ) );
		speak( __( 'Link removed.', 'woocommerce' ), 'assertive' );
	}

	return (
		<>
			<RichTextShortcut type="primary" character="k" onUse={ addLink } />
			<RichTextShortcut
				type="primaryShift"
				character="k"
				onUse={ onRemoveFormat }
			/>
			{ isActive && (
				<FormatToolbarButton
					// name="link"
					icon={ linkOff }
					title={ __( 'Unlink', 'woocommerce' ) }
					onClick={ onRemoveFormat }
					isActive={ isActive }
					// shortcutType="primaryShift"
					// shortcutCharacter="k"
				/>
			) }
			{ ! isActive && (
				<FormatToolbarButton
					// name="link"
					icon={ linkIcon }
					title={ title }
					onClick={ addLink }
					isActive={ isActive }
					// shortcutType="primary"
					// shortcutCharacter="k"
				/>
			) }
			{ ( addingLink || isActive ) && (
				<InlineLinkUI
					addingLink={ addingLink }
					stopAddingLink={ stopAddingLink }
					isActive={ isActive }
					activeAttributes={ activeAttributes }
					value={ value }
					onChange={ onChange }
					contentRef={ contentRef }
				/>
			) }
		</>
	);
}

export const link: ExtendedFormatConfiguration = {
	name,
	title,
	tagName: 'a',
	className: null,
	attributes: {
		url: 'href',
		type: 'data-type',
		id: 'data-id',
		target: 'target',
	},
	__unstablePasteRule(
		value: Value,
		{ html, plainText }: { html: string; plainText: string }
	) {
		if ( isCollapsed( value ) ) {
			return value;
		}

		const pastedText = ( html || plainText )
			.replace( /<[^>]+>/g, '' )
			.trim();

		// A URL was pasted, turn the selection into a link.
		if ( ! isURL( pastedText ) ) {
			return value;
		}

		// Allows us to ask for this information when we get a report.
		window.console.log( 'Created link:\n\n', pastedText );

		return applyFormat( value, {
			type: name,
			attributes: {
				url: decodeEntities( pastedText ),
			},
		} );
	},
	edit: Edit,
};

export const register = () => {
	if ( ! formatIsRegistered( name ) ) {
		registerFormatType( name, link );
	}
};

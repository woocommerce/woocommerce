/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { RichTextShortcut } from '@wordpress/block-editor';
import { formatItalic } from '@wordpress/icons';
import { ToolbarButton } from '@wordpress/components';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore This type exists in @wordpress/rich-text.
	ExtendedFormatConfiguration,
	registerFormatType,
	toggleFormat,
} from '@wordpress/rich-text';

/**
 * Internal dependencies
 */
import { EditProps } from '../types/blocks';
import { formatIsRegistered } from './register-format-types';
import { FormatToolbarButton } from '../components/format-toolbar-button';

const NAME = 'rich-text-editor/italic';
const title = 'Italic';
const tagName = 'em';

export const italic: ExtendedFormatConfiguration = {
	title,
	tagName,
	className: null,
	edit( { isActive, value, onChange, onFocus }: EditProps ) {
		function onClick() {
			onChange( toggleFormat( value, { type: NAME } ) );
			onFocus();
		}

		return (
			<>
				<RichTextShortcut
					type="primary"
					character="i"
					onUse={ onClick }
				/>
				<FormatToolbarButton
					icon={ formatItalic }
					title={ title }
					onClick={ onClick }
					isActive={ isActive }
				/>
			</>
		);
	},
	inactive: () => {
		return (
			<ToolbarButton icon={ formatItalic } title={ title } isDisabled />
		);
	},
};

export const register = () => {
	if ( ! formatIsRegistered( NAME ) ) {
		registerFormatType( NAME, italic );
	}
};

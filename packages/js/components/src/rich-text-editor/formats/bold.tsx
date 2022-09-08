/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { RichTextShortcut } from '@wordpress/block-editor';
import { formatBold } from '@wordpress/icons';
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
import { formatIsRegistered } from './register-format-types';
import { EditProps } from '../types/blocks';
import { FormatToolbarButton } from '../components/format-toolbar-button';

const NAME = 'rich-text-editor/bold';
const title = 'Bold';
const tagName = 'strong';

export const bold: ExtendedFormatConfiguration = {
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
					character="b"
					onUse={ onClick }
				/>
				<FormatToolbarButton
					icon={ formatBold }
					title={ title }
					onClick={ onClick }
					isActive={ isActive }
				/>
			</>
		);
	},
	inactive() {
		return <ToolbarButton icon={ formatBold } title={ title } isDisabled />;
	},
};

export const register = () => {
	if ( ! formatIsRegistered( NAME ) ) {
		registerFormatType( NAME, bold );
	}
};

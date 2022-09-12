/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { Slot, Toolbar, ToolbarGroup } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { HeadingTransform } from '../transforms/heading-transform';
import { ListTransform } from '../transforms/list-transform';
import { QuoteTransform } from '../transforms/quote-transform';
import { bold } from '../formats/bold';
import { italic } from '../formats/italic';
import { link } from '../formats/link';
import { TextAlignmentToolbar } from './text-alignment-toolbar';

export const FORMAT_TOOLBAR_SLOT_NAME = 'rich-text-editor/format-toolbar';

export const FixedFormattingToolbar = () => {
	// Note that the order is important, later we could improve this by having some kind of registry for these inactive buttons
	// that we register to during the registering of format types to ensure order is maintained.
	const inactiveFormatters = [
		{ component: bold.inactive, title: bold.title },
		{ component: italic.inactive, title: italic.title },
		{ component: link.inactive, title: link.title },
	];

	return (
		<div>
			<Toolbar label={ __( ' Formatting options', 'woocommerce' ) }>
				<ToolbarGroup>
					{ /* Heading transforms */ }
					<HeadingTransform headingLevel={ 1 } />
					<HeadingTransform headingLevel={ 2 } />
					<HeadingTransform headingLevel={ 3 } />
					{ /* Rich text formatting options  */ }
					<Slot name={ FORMAT_TOOLBAR_SLOT_NAME }>
						{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
						{ /* @ts-ignore - Type issue here might be an issue with DT types. */ }
						{ ( fills ) => {
							if ( ! fills.length ) {
								return inactiveFormatters.map(
									( { component: Format, title } ) => (
										<Format key={ title } />
									)
								);
							}

							return fills;
						} }
					</Slot>
					{ /* List transforms */ }
					<ListTransform listType="unordered" />
					<ListTransform listType="ordered" />
					<QuoteTransform />
					<TextAlignmentToolbar />
				</ToolbarGroup>
			</Toolbar>
		</div>
	);
};

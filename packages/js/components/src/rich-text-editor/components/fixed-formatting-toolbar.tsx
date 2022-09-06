/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Slot, Toolbar, ToolbarGroup } from '@wordpress/components';
// import { FormattingMenuDivider } from './components/FormattingMenuDivider';
// import { bold } from './formats/bold';
// import { italic } from './formats/italic';
// import { ImageUpload } from './tools/ImageUpload';
// import { ListTransform } from './transforms/ListTransform';

/**
 * Internal dependencies
 */
import { HeadingTransform } from '../transforms/heading-transform';

export const FORMAT_TOOLBAR_SLOT_NAME = 'dayone/format-toolbar';

export const FixedFormattingToolbar = () => {
	// Note that the order is important, later we could improve this by having some kind of registry for these inactive buttons
	// that we register to during the registering of format types to ensure order is maintained.
	// const inactiveFormatters = [
	// 	{ component: bold.inactive, title: bold.title },
	// 	{ component: italic.inactive, title: italic.title },
	// ];
	return (
		<div>
			<Toolbar label="Options">
				{ /* Rich text formatting options  */ }
				<ToolbarGroup />
				{/* <FormattingMenuDivider /> */}
				{ /* Heading transforms */ }
				<ToolbarGroup>
					<HeadingTransform
						isContextMenu={ false }
						headingLevel={ 1 }
					/>
					<HeadingTransform
						isContextMenu={ false }
						headingLevel={ 2 }
					/>
					<HeadingTransform
						isContextMenu={ false }
						headingLevel={ 3 }
					/>
				</ToolbarGroup>
				{ /* <FormattingMenuDivider /> */ }
				{ /* List transforms */ }
				{ /* <ToolbarGroup sx={ toolbarGroupStyle }>
					<ListTransform
						isContextMenu={ false }
						listType="unordered"
					/>
					<ListTransform isContextMenu={ false } listType="ordered" />
					<ListTransform
						isContextMenu={ false }
						listType="checklist"
					/>
				</ToolbarGroup> */ }

				{ /* Other menu items */ }
				{ /* { process.env.NODE_ENV === 'development' && (
					<>
						<FormattingMenuDivider />
						<ToolbarGroup sx={ toolbarGroupStyle }>
							<ImageUpload />
						</ToolbarGroup>
					</>
				) } */ }
			</Toolbar>
		</div>
	);
};

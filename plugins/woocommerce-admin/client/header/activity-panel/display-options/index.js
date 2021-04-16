/**
 * External dependencies
 */
import {
	createSlotFill,
	DropdownMenu,
	MenuGroup,
	MenuItemsChoice,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useUserPreferences, OPTIONS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { DisplayIcon } from './icons/display';
import { SingleColumnIcon } from './icons/single-column';
import { TwoColumnsIcon } from './icons/two-columns';

const { Fill, Slot } = createSlotFill( 'DisplayOptions' );

Fill.Slot = Slot;

export { Fill as DisplayOption };

const LAYOUTS = [
	{
		value: 'single_column',
		label: (
			<>
				<SingleColumnIcon />
				{ __( 'Single column', 'woocommerce-admin' ) }
			</>
		),
	},
	{
		value: 'two_columns',
		label: (
			<>
				<TwoColumnsIcon />
				{ __( 'Two columns', 'woocommerce-admin' ) }
			</>
		),
	},
];

export const DisplayOptions = () => {
	const { defaultHomescreenLayout } = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );
		return {
			defaultHomescreenLayout:
				getOption( 'woocommerce_default_homepage_layout' ) ||
				'single_column',
		};
	} );
	const {
		updateUserPreferences,
		homepage_layout: layout,
	} = useUserPreferences();

	return (
		<Slot>
			{ ( fills ) => (
				<DropdownMenu
					icon={ <DisplayIcon /> }
					/* translators: button label text should, if possible, be under 16 characters. */
					label={ __( 'Display options', 'woocommerce-admin' ) }
					toggleProps={ {
						className:
							'woocommerce-layout__activity-panel-tab display-options',
						onClick: () =>
							recordEvent( 'homescreen_display_click' ),
					} }
					popoverProps={ {
						className: 'woocommerce-layout__activity-panel-popover',
					} }
				>
					{ ( { onClose } ) => (
						<>
							{ fills }
							<MenuGroup
								className="woocommerce-layout__homescreen-display-options"
								label={ __( 'Layout', 'woocommerce-admin' ) }
							>
								<MenuItemsChoice
									choices={ LAYOUTS }
									onSelect={ ( newLayout ) => {
										updateUserPreferences( {
											homepage_layout: newLayout,
										} );
										onClose();
										recordEvent(
											'homescreen_display_option',
											{
												display_option: newLayout,
											}
										);
									} }
									value={ layout || defaultHomescreenLayout }
								/>
							</MenuGroup>
						</>
					) }
				</DropdownMenu>
			) }
		</Slot>
	);
};

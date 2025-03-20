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
import { useUserPreferences, optionsStore } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { DisplayIcon } from './icons/display';
import { SingleColumnIcon } from './icons/single-column';
import { TwoColumnsIcon } from './icons/two-columns';
import { isTaskListActive } from '../../hooks/use-tasklists-state';

const { Fill, Slot } = createSlotFill( 'DisplayOptions' );

Fill.Slot = Slot;

export { Fill as DisplayOption };

const LAYOUTS = [
	{
		value: 'single_column',
		label: (
			<>
				<SingleColumnIcon />
				{ __( 'Single column', 'woocommerce' ) }
			</>
		),
	},
	{
		value: 'two_columns',
		label: (
			<>
				<TwoColumnsIcon />
				{ __( 'Two columns', 'woocommerce' ) }
			</>
		),
	},
];

export const DisplayOptions = () => {
	const { defaultHomescreenLayout } = useSelect( ( select ) => {
		const { getOption } = select( optionsStore );

		return {
			defaultHomescreenLayout:
				getOption( 'woocommerce_default_homepage_layout' ) ||
				'single_column',
		};
	} );

	const { updateUserPreferences, homepage_layout: layout } =
		useUserPreferences();

	const hasTwoColumnContent =
		! isTaskListActive( 'setup' ) || window.wcAdminFeatures.analytics;

	return (
		<Slot>
			{ ( fills ) => {
				// If there is no fill to render and only single column content, don't render the display.
				if ( fills.length === 0 && ! hasTwoColumnContent ) {
					return null;
				}
				return (
					<DropdownMenu
						icon={ <DisplayIcon /> }
						/* translators: button label text should, if possible, be under 16 characters. */
						label={ __( 'Display options', 'woocommerce' ) }
						toggleProps={ {
							className:
								'woocommerce-layout__activity-panel-tab display-options',
							onClick: () =>
								recordEvent( 'homescreen_display_click' ),
						} }
						popoverProps={ {
							className:
								'woocommerce-layout__activity-panel-popover',
						} }
					>
						{ ( { onClose } ) => (
							<>
								{ fills }
								{ hasTwoColumnContent ? (
									<MenuGroup
										className="woocommerce-layout__homescreen-display-options"
										label={ __( 'Layout', 'woocommerce' ) }
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
														display_option:
															newLayout,
													}
												);
											} }
											value={
												layout ||
												defaultHomescreenLayout
											}
										/>
									</MenuGroup>
								) : null }
							</>
						) }
					</DropdownMenu>
				);
			} }
		</Slot>
	);
};

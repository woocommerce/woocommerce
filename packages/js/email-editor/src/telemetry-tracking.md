# Editor Telemetry Tracking

The email editor has a built in system for tracking it's telemetry that can be used to track users interactions with the editor.

## Enabling Tracking

To enable tracking an integrator needs to add the following filters.

```js
// enable email editor event tracking
addFilter(
	'woocommerce_email_editor_events_tracking_enabled',
	'your_plugin_namespace`,
	() => true
);

// process events tracked in the editor
addAction( 'woocommerce_email_editor_events', 'your_plugin_namespace', ( editorEvent ) => {
	const { name, ...data } = editorEvent;
	// Replace console.log with your tracking code
  console.log( name, data );
```

## Tracked events

All events are prefixed `email_editor_events_`.

### Table of events

| Event Name | Data | Description |
|------------|------|-------------|
| `block_controls_personalization_tags_button_clicked` | - | Tracked when the personalization tags button is clicked in block controls |
| `command_menu_opened` | - | Tracked when command menu is opened via click or a shortcut |
| `command_menu_closed` | - | Tracked when command menu is closed |
| `command_bar_command_clicked` | `{ command }` | Tracked when a command is selected by a click. |
| `edit_template_modal_cancel_button_clicked` | - | Tracked when the cancel button is clicked in the edit template modal |
| `edit_template_modal_closed` | - | Tracked when the edit template modal is closed |
| `edit_template_modal_continue_button_clicked` | `{ templateId }` | Tracked when the continue button is clicked in the edit template modal |
| `edit_template_modal_opened` | - | Tracked when the edit template modal is opened |
| `editor_layout_loaded` | - | Tracked when the editor layout is loaded |
| `editor_showed_email_sent_notice` | - | Tracked when the editor shows the email sent notice |
| `header_blocks_tool_button_clicked` | `{ isOpened }` | Tracked when user clicks the blocks tool button in the header to toggle header block tools |
| `header_close_button_clicked` | - | Tracked when user clicks the back button in the header |
| `header_inserter_sidebar_clicked` | `{ isOpened }` | Tracked when user clicks the inserter sidebar button in the header |
| `header_listview_sidebar_clicked` | `{ isOpened }` | Tracked when user clicks the listview sidebar button in the header |
| `header_more_menu_dropdown_toggle` | `{ isOpened }` | Tracked when user toggles the more menu dropdown in the header |
| `header_preview_dropdown_clicked` | `{ isOpened }` | Tracked when user clicks an action in the preview dropdown |
| `header_preview_dropdown_${deviceType}_selected` | - | Tracked when a device type is selected from the header preview dropdown (e.g., desktop, mobile, tablet) |
| `header_preview_dropdown_preview_in_new_tab_selected` | - | Tracked when the preview in new tab option is selected from the header preview dropdown |
| `header_preview_dropdown_send_test_email_selected` | - | Tracked when the send test email option is selected from the header preview dropdown |
| `header_save_button_clicked` | - | Tracked when user clicks the save button in the header |
| `header_send_button_clicked` | - | Tracked when user clicks the send button in the header |
| `personalization_tags_modal_category_menu_clicked` | `{ category, openedBy }` | Tracked when a category is selected in the personalization tags modal |
| `personalization_tags_modal_closed` | `{ openedBy }` | Tracked when the personalization tags modal is closed |
| `personalization_tags_modal_learn_more_link_clicked` | `{ openedBy }` | Tracked when the learn more link is clicked in the personalization tags modal |
| `personalization_tags_modal_opened` | `{ openedBy }` | Tracked when the personalization tags modal is opened. The openedBy parameter indicates where it was opened from (e.g., 'block-controls', 'RichTextWithButton-BaseControl') |
| `personalization_tags_modal_search_control_input_updated` | `{ openedBy }` | Tracked when the search input is updated in the personalization tags modal |
| `personalization_tags_modal_tag_insert_button_clicked` | `{ insertedTag, activeCategory, openedBy }` | Tracked when a tag insert button is clicked in the personalization tags modal |
| `preview_dropdown_rendering_mode_changed` | `{ renderingMode }` | Tracked when user toggles "Show template" in preview menu. Values are 'template-locked', 'post-only'. |
| `rich_text_with_button_input_field_updated` | `{ attributeName }` | Tracked when the input field is updated in a rich text with button component |
| `rich_text_with_button_personalization_tags_inserted` | `{ attributeName, value }` | Tracked when a personalization tag is inserted in a rich text with button component |
| `rich_text_with_button_personalization_tags_shortcode_icon_clicked` | `{ attributeName, label }` | Tracked when the personalization tags shortcode icon is clicked in a rich text with button component |
| `send_preview_email_modal_check_sending_method_configuration_link_clicked` | - | Tracked when the sending method configuration link is clicked in the send preview email modal |
| `send_preview_email_modal_closed` | - | Tracked when the send preview email modal is closed |
| `send_preview_email_modal_close_button_clicked` | - | Tracked when the close button is clicked in the send preview email modal |
| `send_preview_email_modal_opened` | - | Tracked when the send preview email modal is opened |
| `send_preview_email_modal_send_test_email_button_clicked` | - | Tracked when the send test email button is clicked in the send preview email modal |
| `send_preview_email_modal_send_to_field_key_code_enter` | - | Tracked when the enter key is pressed in the send to field of the send preview email modal |
| `send_preview_email_modal_send_to_field_updated` | - | Tracked when the send to field is updated in the send preview email modal |
| `send_preview_email_modal_sign_up_for_mailpoet_sending_service_link_clicked` | - | Tracked when the sign up for MailPoet Sending Service link is clicked in the send preview email modal |
| `sent_preview_email` | `{ postId, email }` | Tracked when a preview email is successfully sent |
| `sent_preview_email_error` | `{ email }` | Tracked when there's an error sending a preview email |
| `sidebar_tab_selected` | `{ tab: 'document' \| 'block' }` | Tracked when user selects a tab in the sidebar |
| `styles_sidebar_navigation_click` | `{ path }` | Tracked when a navigation button is clicked in the styles sidebar (e.g., typography, colors, layout) |
| `styles_sidebar_screen_colors_opened` | - | Tracked when the colors screen in styles sidebar is opened |
| `styles_sidebar_screen_colors_styles_updated` | - | Tracked when colors are updated in the styles sidebar |
| `styles_sidebar_screen_layout_dimensions_block_spacing_reset_clicked` | - | Tracked when the block spacing reset button is clicked in the dimensions panel |
| `styles_sidebar_screen_layout_dimensions_block_spacing_updated` | `{ value }` | Tracked when block spacing is updated in the dimensions panel |
| `styles_sidebar_screen_layout_dimensions_padding_reset_clicked` | - | Tracked when the padding reset button is clicked in the dimensions panel |
| `styles_sidebar_screen_layout_dimensions_padding_updated` | `{ value }` | Tracked when padding is updated in the dimensions panel |
| `styles_sidebar_screen_layout_dimensions_reset_all_selected` | - | Tracked when the reset all button is clicked in the dimensions panel |
| `styles_sidebar_screen_layout_opened` | - | Tracked when the layout screen in styles sidebar is opened |
| `styles_sidebar_screen_typography_button_click` | `{ element, label, path }` | Tracked when a typography element button is clicked in the typography panel |
| `styles_sidebar_screen_typography_element_heading_level_selected` | `{ value }` | Tracked when a heading level is selected in the typography element screen |
| `styles_sidebar_screen_typography_element_opened` | `{ element }` | Tracked when the typography screen in styles sidebar is opened |
| `styles_sidebar_screen_typography_element_panel_reset_all_styles_selected` | `{ element, headingLevel }` | Tracked when the reset all styles button is clicked in the typography element panel |
| `styles_sidebar_screen_typography_opened` | - | Tracked when the typography screen in styles sidebar is opened |
| `template_select_modal_category_change` | `{ category }` | Tracked when user changes category in template select modal |
| `template_select_modal_closed` | `{ templateSelectMode }` | Tracked when template select modal is closed. templateSelectMode tracks category which was opened. |
| `template_select_modal_handle_close_without_template_selected` | - | Tracked when the template select modal is closed without explicitly selecting a template |
| `template_select_modal_opened` | `{ templateSelectMode }` | Tracked when template select modal is opened |
| `template_select_modal_start_from_scratch_clicked` | - | Tracked when the "Start from scratch" button is clicked in the template select modal |
| `template_select_modal_template_selected` | `{ template }` | Tracked when user selects a template in the template select modal |
| `trash_modal_move_to_trash_button_clicked` | - | Tracked when user clicks the move to trash button in the trash modal |
| `styles_sidebar_screen_typography_element_panel_set_letter_spacing` | `{ element, newValue, selectedDefaultLetterSpacing }` | Tracked when letter spacing is changed in typography panel |
| `styles_sidebar_screen_typography_element_panel_set_line_height` | `{ element, newValue, selectedDefaultLineHeight }` | Tracked when line height is changed in typography panel |
| `styles_sidebar_screen_typography_element_panel_set_font_size` | `{ element, newValue, selectedDefaultFontSize }` | Tracked when font size is changed in typography panel |
| `styles_sidebar_screen_typography_element_panel_set_font_family` | `{  element, newValue, selectedDefaultFontFamily }` | Tracked when font family is changed in typography panel |
| `styles_sidebar_screen_typography_element_panel_set_text_decoration` | `{ element, newValue, selectedDefaultTextDecoration }` | Tracked when text decoration is changed in typography panel |
| `styles_sidebar_screen_typography_element_panel_set_text_transform` | `{ element, newValue, selectedDefaultTextDecoration }` | Tracked when test transform is changed in typography panel |
| `styles_sidebar_screen_typography_element_panel_set_font_appearance` | `{ element, newFontStyle, newFontWeight, selectedDefaultFontStyle, selectedDefaultFontWeight  }` | Tracked when font appearance is changed in typography panel |
| `styles_sidebar_screen_typography_element_panel_reset_all_styles_selected` | - | Tracked when all styles are reset |
| `{$inserter}_library_block_selected` | `{ blockName }` | Tracked when a block is inserted via an inserter. Inserter types: inserter_sidebar, quick_inserter, other_inserter. |
| `{$inserter}_library_pattern_selected` | `{ patternName }` | Tracked when a pattern is inserted via an inserter. Inserter types: inserter_sidebar, quick_inserter, other_inserter. |

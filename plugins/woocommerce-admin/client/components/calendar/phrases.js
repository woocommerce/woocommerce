/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

export default {
	calendarLabel: __( 'Calendar', 'woo-dash' ),
	closeDatePicker: __( 'Close', 'woo-dash' ),
	focusStartDate: __( 'Interact with the calendar and select start and end dates.', 'woo-dash' ),
	clearDate: __( 'Clear Date', 'woo-dash' ),
	clearDates: __( 'Clear Dates', 'woo-dash' ),
	jumpToPrevMonth: __( 'Move backward to switch to the previous month.', 'woo-dash' ),
	jumpToNextMonth: __( 'Move forward to switch to the next month.', 'woo-dash' ),
	enterKey: __( 'Enter key', 'woo-dash' ),
	leftArrowRightArrow: __( 'Right and left arrow keys', 'woo-dash' ),
	upArrowDownArrow: __( 'up and down arrow keys', 'woo-dash' ),
	pageUpPageDown: __( 'page up and page down keys', 'woo-dash' ),
	homeEnd: __( 'Home and end keys', 'woo-dash' ),
	escape: __( 'Escape key', 'woo-dash' ),
	questionMark: __( 'Question mark', 'woo-dash' ),
	selectFocusedDate: __( 'Select the date in focus.', 'woo-dash' ),
	moveFocusByOneDay: __( 'Move backward (left) and forward (right) by one day.', 'woo-dash' ),
	moveFocusByOneWeek: __( 'Move backward (up) and forward (down) by one week.', 'woo-dash' ),
	moveFocusByOneMonth: __( 'Switch months.', 'woo-dash' ),
	moveFocustoStartAndEndOfWeek: __( 'Go to the first or last day of a week.', 'woo-dash' ),
	returnFocusToInput: __( 'Return to the date input field.', 'woo-dash' ),
	keyboardNavigationInstructions: __(
		`Press the down arrow key to interact with the calendar and
  select a date.`,
		'woo-dash'
	),
	chooseAvailableStartDate: ( { date } ) =>
		sprintf( __( 'Select %s as a start date.', 'woo-dash' ), date ),
	chooseAvailableEndDate: ( { date } ) =>
		sprintf( __( 'Select %s as an end date.', 'woo-dash' ), date ),
	chooseAvailableDate: ( { date } ) => date,
	dateIsUnavailable: ( { date } ) => sprintf( __( '%s is not selectable.', 'woo-dash' ), date ),
	dateIsSelected: ( { date } ) => sprintf( __( 'Selected. %s', 'woo-dash' ), date ),
};

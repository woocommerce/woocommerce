/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

export default {
	calendarLabel: __( 'Calendar', 'wc-admin' ),
	closeDatePicker: __( 'Close', 'wc-admin' ),
	focusStartDate: __( 'Interact with the calendar and select start and end dates.', 'wc-admin' ),
	clearDate: __( 'Clear Date', 'wc-admin' ),
	clearDates: __( 'Clear Dates', 'wc-admin' ),
	jumpToPrevMonth: __( 'Move backward to switch to the previous month.', 'wc-admin' ),
	jumpToNextMonth: __( 'Move forward to switch to the next month.', 'wc-admin' ),
	enterKey: __( 'Enter key', 'wc-admin' ),
	leftArrowRightArrow: __( 'Right and left arrow keys', 'wc-admin' ),
	upArrowDownArrow: __( 'up and down arrow keys', 'wc-admin' ),
	pageUpPageDown: __( 'page up and page down keys', 'wc-admin' ),
	homeEnd: __( 'Home and end keys', 'wc-admin' ),
	escape: __( 'Escape key', 'wc-admin' ),
	questionMark: __( 'Question mark', 'wc-admin' ),
	selectFocusedDate: __( 'Select the date in focus.', 'wc-admin' ),
	moveFocusByOneDay: __( 'Move backward (left) and forward (right) by one day.', 'wc-admin' ),
	moveFocusByOneWeek: __( 'Move backward (up) and forward (down) by one week.', 'wc-admin' ),
	moveFocusByOneMonth: __( 'Switch months.', 'wc-admin' ),
	moveFocustoStartAndEndOfWeek: __( 'Go to the first or last day of a week.', 'wc-admin' ),
	returnFocusToInput: __( 'Return to the date input field.', 'wc-admin' ),
	keyboardNavigationInstructions: __(
		`Press the down arrow key to interact with the calendar and
  select a date.`,
		'wc-admin'
	),
	chooseAvailableStartDate: ( { date } ) =>
		sprintf( __( 'Select %s as a start date.', 'wc-admin' ), date ),
	chooseAvailableEndDate: ( { date } ) =>
		sprintf( __( 'Select %s as an end date.', 'wc-admin' ), date ),
	chooseAvailableDate: ( { date } ) => date,
	dateIsUnavailable: ( { date } ) => sprintf( __( '%s is not selectable.', 'wc-admin' ), date ),
	dateIsSelected: ( { date } ) => sprintf( __( 'Selected. %s', 'wc-admin' ), date ),
};

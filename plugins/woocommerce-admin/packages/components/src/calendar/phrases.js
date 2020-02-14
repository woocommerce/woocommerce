/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

export default {
	calendarLabel: __( 'Calendar', 'woocommerce-admin' ),
	closeDatePicker: __( 'Close', 'woocommerce-admin' ),
	focusStartDate: __(
		'Interact with the calendar and select start and end dates.',
		'woocommerce-admin'
	),
	clearDate: __( 'Clear Date', 'woocommerce-admin' ),
	clearDates: __( 'Clear Dates', 'woocommerce-admin' ),
	jumpToPrevMonth: __(
		'Move backward to switch to the previous month.',
		'woocommerce-admin'
	),
	jumpToNextMonth: __(
		'Move forward to switch to the next month.',
		'woocommerce-admin'
	),
	enterKey: __( 'Enter key', 'woocommerce-admin' ),
	leftArrowRightArrow: __( 'Right and left arrow keys', 'woocommerce-admin' ),
	upArrowDownArrow: __( 'up and down arrow keys', 'woocommerce-admin' ),
	pageUpPageDown: __( 'page up and page down keys', 'woocommerce-admin' ),
	homeEnd: __( 'Home and end keys', 'woocommerce-admin' ),
	escape: __( 'Escape key', 'woocommerce-admin' ),
	questionMark: __( 'Question mark', 'woocommerce-admin' ),
	selectFocusedDate: __( 'Select the date in focus.', 'woocommerce-admin' ),
	moveFocusByOneDay: __(
		'Move backward (left) and forward (right) by one day.',
		'woocommerce-admin'
	),
	moveFocusByOneWeek: __(
		'Move backward (up) and forward (down) by one week.',
		'woocommerce-admin'
	),
	moveFocusByOneMonth: __( 'Switch months.', 'woocommerce-admin' ),
	moveFocustoStartAndEndOfWeek: __(
		'Go to the first or last day of a week.',
		'woocommerce-admin'
	),
	returnFocusToInput: __(
		'Return to the date input field.',
		'woocommerce-admin'
	),
	keyboardNavigationInstructions: __(
		'Press the down arrow key to interact with the calendar and select a date.',
		'woocommerce-admin'
	),
	chooseAvailableStartDate: ( { date } ) =>
		sprintf(
			__( 'Select %s as a start date.', 'woocommerce-admin' ),
			date
		),
	chooseAvailableEndDate: ( { date } ) =>
		sprintf( __( 'Select %s as an end date.', 'woocommerce-admin' ), date ),
	chooseAvailableDate: ( { date } ) => date,
	dateIsUnavailable: ( { date } ) =>
		sprintf( __( '%s is not selectable.', 'woocommerce-admin' ), date ),
	dateIsSelected: ( { date } ) =>
		sprintf( __( 'Selected. %s', 'woocommerce-admin' ), date ),
};

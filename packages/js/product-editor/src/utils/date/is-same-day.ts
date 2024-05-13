export function isSameDay( left: Date, right: Date ) {
	return (
		left.getDate() === right.getDate() &&
		left.getMonth() === right.getMonth() &&
		left.getFullYear() === right.getFullYear()
	);
}

/**
 * External dependencies
 */
import { SVG } from '@wordpress/primitives';

const FeedbackIcon = ( { size = 12 }: { size?: number } ) => (
	<SVG
		width={ size }
		height={ size }
		viewBox="0 0 12 12"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M2.45865 9.08341L1.6665 9.87639L1.6665 1.66675L10.3332 1.66675L10.3332 9.08341L2.45865 9.08341ZM2.87317 10.0834L10.6665 10.0834C11.0347 10.0834 11.3332 9.78494 11.3332 9.41675L11.3332 1.33342C11.3332 0.965226 11.0347 0.666748 10.6665 0.666748H1.33317C0.964982 0.666748 0.666504 0.965225 0.666504 1.33341V11.0166C0.666504 11.2116 0.773993 11.3907 0.946074 11.4825C1.15124 11.5919 1.40385 11.5543 1.56818 11.3898L2.87317 10.0834ZM8.6665 4.66673H3.33317V3.66673H8.6665V4.66673ZM3.33317 7.33339H6.6665V6.33339H3.33317V7.33339Z"
			fill="currentColor"
		/>
	</SVG>
);

export default FeedbackIcon;

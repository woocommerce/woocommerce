/**
 * External dependencies
 */
import { SVG, G, Path } from '@wordpress/components';
import { createElement } from '@wordpress/element';

export function CashRegister( {
	colorOne = '#E0E0E0',
	colorTwo = '#F0F0F0',
	size = '88',
	style = {},
} ) {
	return (
		<SVG
			width={ size }
			height={ size }
			viewBox="0 0 88 88"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
			style={ style }
		>
			<G clipPath="url(#clip0_13540_198076)">
				<Path
					d="M77.2539 14.7807L39.9517 14.6667C35.4172 14.6667 32.8506 17.199 32.8506 21.718V36.7241L10.818 36.6997C6.29575 36.6997 3.76167 39.2645 3.76167 43.7957L3.66797 81.0294L84.3632 81.0742V21.8319C84.3632 17.313 81.7965 14.7807 77.262 14.7807H77.2539Z"
					fill={ colorOne }
				/>
				<Path
					d="M47.5672 47.6794H40.2461V54.9953H47.5672V47.6794Z"
					fill={ colorTwo }
				/>
				<Path
					d="M62.3836 47.6794H55.0625V54.9953H62.3836V47.6794Z"
					fill={ colorTwo }
				/>
				<Path
					d="M77.0242 47.6794H69.7031V54.9953H77.0242V47.6794Z"
					fill={ colorTwo }
				/>
				<Path
					d="M47.5672 62.3232H40.2461V69.6391H47.5672V62.3232Z"
					fill={ colorTwo }
				/>
				<Path
					d="M62.3836 62.3232H55.0625V69.6391H62.3836V62.3232Z"
					fill={ colorTwo }
				/>
				<Path
					d="M76.9617 62.3232H69.6406V69.6391H76.9617V62.3232Z"
					fill={ colorTwo }
				/>
				<Path
					d="M77.0221 36.6795L40.3555 36.7243V22.0682L77.0221 22.0234V36.6795Z"
					fill={ colorTwo }
				/>
				<Path
					d="M88 80.8988V80.7034L0 80.6667V87.9581L88 87.9948V80.8988Z"
					fill={ colorTwo }
				/>
				<Path
					d="M29.4451 14.6667C27.844 14.6667 27.3225 16.6901 25.7621 16.6901C24.2018 16.6901 23.6844 14.6667 22.0832 14.6667C20.4821 14.6667 19.9607 16.6901 18.4003 16.6901C16.8399 16.6901 16.3225 14.6667 14.7173 14.6667C13.1121 14.6667 12.5947 16.6901 11.0344 16.6901C9.47399 16.6901 8.95658 14.6667 7.35547 14.6667V19.5643V62.3275H29.4451V14.6667Z"
					fill={ colorTwo }
				/>
			</G>
		</SVG>
	);
}

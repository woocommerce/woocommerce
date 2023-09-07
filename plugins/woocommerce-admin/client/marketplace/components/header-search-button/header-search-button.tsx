/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

export default function HeaderSearchButton() {
	return (
		<button className="woocommerce-marketplace__header-search-button">
			<svg
				width="24"
				height="24"
				viewBox="0 0 24 24"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
			>
				<path
					id="Union"
					fillRule="evenodd"
					clipRule="evenodd"
					d="M19.0001 11C19.0001 14.3137 16.3138 17 13.0001 17C11.6135 17 10.3369 16.5297 9.32086 15.7399L5.53039 19.5304L4.46973 18.4697L8.26019 14.6793C7.47038 13.6632 7.00006 12.3865 7.00006 11C7.00006 7.68629 9.68635 5 13.0001 5C16.3138 5 19.0001 7.68629 19.0001 11ZM17.5001 11C17.5001 13.4853 15.4853 15.5 13.0001 15.5C10.5148 15.5 8.50006 13.4853 8.50006 11C8.50006 8.51472 10.5148 6.5 13.0001 6.5C15.4853 6.5 17.5001 8.51472 17.5001 11Z"
					fill="#1E1E1E"
				/>
			</svg>
			<span className="screen-reader-text">
				{ __( 'Search', 'woocommerce' ) }
			</span>
		</button>
	);
}

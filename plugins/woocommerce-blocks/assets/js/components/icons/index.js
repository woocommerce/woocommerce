/**
 * External dependencies
 */
import { Icon } from '@wordpress/components';

export const IconChecked = () => (
	<Icon
		icon={
			<svg
				viewBox="0 0 16 16"
				width="16"
				height="16"
				xmlns="http://www.w3.org/2000/svg"
			>
				<defs>
					<path
						id="checked"
						d="M15.2222 1H2.7778C1.791 1 1 1.8 1 2.7778v12.4444C1 16.2 1.7911 17 2.7778 17h12.4444C16.209 17 17 16.2 17 15.2222V2.7778C17 1.8 16.2089 1 15.2222 1zm-8 12.4444L2.7778 9 4.031 7.7467l3.1911 3.1822 6.7467-6.7467 1.2533 1.2622-8 8z"
					/>
				</defs>
				<g fill="none" fillRule="evenodd" transform="translate(-1 -1)">
					<mask id="checked-mask" fill="#fff">
						<use xlinkHref="#checked" />
					</mask>
					<path fill="#1E8CBE" d="M0 0h18v18H0z" mask="url(#checked-mask)" />
				</g>
			</svg>
		}
	/>
);

export const IconUnchecked = () => (
	<Icon
		icon={
			<svg
				viewBox="0 0 16 16"
				width="16"
				height="16"
				xmlns="http://www.w3.org/2000/svg"
			>
				<defs>
					<path
						id="unchecked"
						d="M15.2222 2.7778v12.4444H2.7778V2.7778h12.4444zm0-1.7778H2.7778C1.8 1 1 1.8 1 2.7778v12.4444C1 16.2 1.8 17 2.7778 17h12.4444C16.2 17 17 16.2 17 15.2222V2.7778C17 1.8 16.2 1 15.2222 1z"
					/>
				</defs>
				<g fill="none" fillRule="evenodd" transform="translate(-1 -1)">
					<mask id="unchecked-mask" fill="#fff">
						<use xlinkHref="#unchecked" />
					</mask>
					<path fill="#6C7781" d="M0 0h18v18H0z" mask="url(#unchecked-mask)" />
				</g>
			</svg>
		}
	/>
);

export const IconNewReleases = () => (
	<Icon
		icon={
			<svg
				xmlns="http://www.w3.org/2000/svg"
				width="24"
				height="24"
				viewBox="0 0 24 24"
			>
				<path d="M0 0h24v24H0z" fill="none" />
				<path d="M23 12l-2.44-2.78.34-3.68-3.61-.82-1.89-3.18L12 3 8.6 1.54 6.71 4.72l-3.61.81.34 3.68L1 12l2.44 2.78-.34 3.69 3.61.82 1.89 3.18L12 21l3.4 1.46 1.89-3.18 3.61-.82-.34-3.68L23 12zm-10 5h-2v-2h2v2zm0-4h-2V7h2v6z" />
			</svg>
		}
	/>
);

export const IconWidgets = () => (
	<Icon
		icon={
			<svg
				xmlns="http://www.w3.org/2000/svg"
				width="24"
				height="24"
				viewBox="0 0 24 24"
			>
				<path d="M13 13v8h8v-8h-8zM3 21h8v-8H3v8zM3 3v8h8V3H3zm13.66-1.31L11 7.34 16.66 13l5.66-5.66-5.66-5.65z" />
				<path d="M0 0h24v24H0z" fill="none" />
			</svg>
		}
	/>
);

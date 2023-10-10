/**
 * External dependencies
 */
import { resolveSelect, useDispatch, useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { SINGLE_VARIATION_NOTICE_DISMISSED_OPTION } from '../../constants';

export function useNotice() {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const { dismissedNotices } = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );

		const dismissedNoticesOption = getOption(
			SINGLE_VARIATION_NOTICE_DISMISSED_OPTION
		) as { [ key: string ]: [ number ] };
		return {
			dismissedNotices: dismissedNoticesOption || {},
		};
	}, [] );

	const getOptions = async () => {
		const { getOption } = resolveSelect( OPTIONS_STORE_NAME );

		const dismissedNoticesOption = ( await getOption(
			SINGLE_VARIATION_NOTICE_DISMISSED_OPTION
		) ) as { [ key: string ]: [ number ] };

		return {
			dismissedNoticesOption,
		};
	};

	const dismissNotice = async ( notice: string, productId: number ) => {
		const { dismissedNoticesOption } = await getOptions();
		if ( Array.isArray( dismissedNoticesOption ) ) {
			updateOptions( {
				[ SINGLE_VARIATION_NOTICE_DISMISSED_OPTION ]: {
					...dismissedNoticesOption,
					[ notice ]: dismissedNoticesOption.hasOwnProperty(
						productId
					)
						? dismissedNoticesOption[ notice ].push( productId )
						: [ productId ],
				},
			} );
		} else {
			updateOptions( {
				[ SINGLE_VARIATION_NOTICE_DISMISSED_OPTION ]: {
					[ notice ]: [ productId ],
				},
			} );
		}
	};

	return {
		dismissedNotices,
		dismissNotice,
	};
}

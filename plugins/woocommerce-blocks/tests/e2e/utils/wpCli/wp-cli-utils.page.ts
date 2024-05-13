/**
 * External dependencies
 */
import { cli } from '@woocommerce/e2e-utils';

type WPCLIResponse = {
	stdout: string;
	stderr: string;
	code: number;
	error: Error | null;
};

export class WPCLIUtils {
	async getCouponIDByCode( code: string ) {
		const response = ( await cli(
			`npm run wp-env run tests-cli -- wp wc shop_coupon list --fields=id --field=id --code="${ code }" --format=csv --user=1`
		) ) as WPCLIResponse;
		return response.stdout.match( /\d+/g )?.pop();
	}

	async getPostIDByTitle( title: string ): Promise< string | undefined > {
		const response = ( await cli(
			`npm run wp-env run tests-cli -- wp post list --title="${ title }" --post_type=page --field=ID`
		) ) as WPCLIResponse;
		return response.stdout.match( /\d+/g )?.pop();
	}
}

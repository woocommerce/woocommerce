const { USER_KEY, USER_ID, RUN_WP_CLI_ON_DOCKER } = process.env;
const util = require('util');
const exec = util.promisify(require('child_process').exec);

const exec_wp_cli = async( command_line_args ) => {
	let command = `wp --user=${JSON.stringify(USER_ID ? USER_ID : USER_KEY)} `;
	if( RUN_WP_CLI_ON_DOCKER == 'true' ) {
		command += '--ssh=docker:root@woocommerce_wordpress-cli --allow-root ';
	}
	command += command_line_args;

	const { stdout } = await exec( command );
	return stdout;
};

const itIf = ( condition ) =>
	condition ? it : it.skip;

module.exports = {
	exec_wp_cli,
	itIf
};

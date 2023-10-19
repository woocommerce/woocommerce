const { exec, execSync } = require("child_process");

const EDIT_SITE_DIR = execSync("find ~+ ../../node_modules/.pnpm -type d -name '@wordpress+edit-site@5.15.0*' -print -quit").toString().trim();


execSync(`find "${EDIT_SITE_DIR}/node_modules/@wordpress/edit-site/build-module/lock-unlock.js" -type f -exec sed -i -e 's/I know using unstable features means my plugin or theme will inevitably break on the next WordPress release/I know using unstable features means my theme or plugin will inevitably break in the next version of WordPress/g' \{\} \+`);

const { exec, execSync } = require("child_process");

// Find edit-site module directory
const EDIT_SITE_DIR = execSync("find ~+ ../../node_modules/.pnpm -type d -name '@wordpress+edit-site@5.15.0*' -print -quit").toString().trim();

// Replace it with modified version
execSync(`cp -rf "./bin/modified-editsite-lock-unlock.js" ${EDIT_SITE_DIR}/node_modules/@wordpress/edit-site/build-module/lock-unlock.js`);

#!/usr/bin/env node
const path = require('node:path');
const { Command } = require('commander');
const slug = require('slug');
const spawn = require('child_process').spawn;
const package_info = require('./lib/package-info');

const command_root = __dirname;
const path_to_template = path.join(command_root, 'assets/new/template');

const program = new Command();
program
    .version(package_info.version, '-v, --version', 'output the current version')
    .command('new')
    .argument('<extension_name>')
    .alias('n')
    .action((extension_name) => {
        const extension_slug = slug(extension_name);
        console.log(`calling npx to generate scaffold for extension "${extension_slug}"`);
        spawn('npx', ['@wordpress/create-block', '--wp-env', '--template', path_to_template, extension_slug], { stdio: 'inherit' });
    });

program.parse(process.argv);

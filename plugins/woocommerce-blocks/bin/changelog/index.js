#!/usr/bin/env node
'use strict';

const { makeChangeLog: githubMake } = require( './github' );
const { makeChangeLog: zenhubMake } = require( './zenhub' );
const { pkg } = require( './config' );

const makeChangeLog = pkg.changelog.zenhub ? zenhubMake : githubMake;

makeChangeLog();

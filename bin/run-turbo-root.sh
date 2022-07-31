#!/bin/bash
pnpm -- turbo run "$1" -- -- ${@:2}

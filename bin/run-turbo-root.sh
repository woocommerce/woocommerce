#!/bin/bash
pnpm exec turbo run "$1" -- -- ${@:2}

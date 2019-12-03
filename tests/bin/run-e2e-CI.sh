#!/usr/bin/env bash
npm install;
echo "Waiting for 2 minutes for the Docker container to start...";
sleep 120;
npm run test:e2e;

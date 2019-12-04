#!/usr/bin/env bash
echo "Waiting for 5 minutes for the Docker container to start...";
sleep 300;
npm run test:e2e;

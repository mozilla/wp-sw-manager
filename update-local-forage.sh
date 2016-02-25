#!/usr/bin/env bash
type bower >/dev/null 2>&1 || { echo >&2 "I require bower but it's not installed.  Aborting."; exit 1; }
bower install -p localforage
mv lib/js/localforage/dist/localforage.nopromises.min.js lib/js/
rm -r lib/js/localforage

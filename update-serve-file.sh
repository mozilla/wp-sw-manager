#!/usr/bin/env bash
type curl >/dev/null 2>&1 || { echo >&2 "I require cURL but it's not installed.  Aborting."; exit 1; }
curl -# https://raw.githubusercontent.com/marco-c/WP_Serve_File/master/class-wp-serve-file.php > ./lib/class-wp-serve-file.php

#!/bin/sh
# Rebuild php/htdocs/assets/app.css after changing Tailwind classes in PHP/JS files.
# Requires Node.js. Run from the repository root: sh php/build/build-css.sh
set -e
cd "$(dirname "$0")"
npx -y @tailwindcss/cli@4.3.5 -i app.css -o ../htdocs/assets/app.css --minify

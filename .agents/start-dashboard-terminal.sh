#!/bin/bash

# Start dashboard in a new Terminal window (macOS)
osascript <<EOF
tell application "Terminal"
    do script "cd '$PWD' && npm run dashboard"
    activate
end tell
EOF

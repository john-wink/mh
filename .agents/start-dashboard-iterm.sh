#!/bin/bash

# Start dashboard in a new iTerm2 window
osascript <<EOF
tell application "iTerm"
    create window with default profile
    tell current session of current window
        write text "cd '$PWD' && npm run dashboard"
    end tell
    activate
end tell
EOF

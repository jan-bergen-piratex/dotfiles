#!/usr/bin/env bash

killall -q polybar || true
while pgrep -u "$UID" -x polybar >/dev/null; do sleep 1; done
polybar -q main -c "$HOME/.config/polybar/config.ini" &

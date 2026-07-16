#!/bin/sh
set -eu

internal=$(xrandr --query | awk '/ connected primary/{print $1; exit} / connected/{if (!seen) {seen=1; first=$1}} END{if (!internal && first) print first}')
external=$(xrandr --query | awk -v internal="$internal" '$2 == "connected" && $1 != internal {print $1; exit}')

if [ -z "${internal:-}" ]; then
  exit 0
fi

if [ -n "${external:-}" ]; then
  xrandr --output "$internal" --auto --primary --output "$external" --auto --right-of "$internal"
else
  xrandr --output "$internal" --auto --primary
fi

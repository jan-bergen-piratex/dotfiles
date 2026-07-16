alias la='ls -a --color'
alias top='ytop -c monokai'
alias ocat='/usr/bin/cat'
alias otop='/usr/bin/top'
alias dusto='dust --depth 1'
alias i='sudo apt install $1'
alias sus='systemctl suspend -i'
alias xc='xclip'
alias xcc='xclip -selection clipboard'
alias commit='git commit -a -m'
alias ccargo='clear && cargo'
alias fd='fd -H'
alias amogus='sus'
alias sl='sl -e'
alias tetris='bastet'
alias dc='cmatrix'
alias b='cd ..'
alias twm='typst watch main.typ'
alias twmo='typst watch main.typ out.pdf'
alias sr='scenery r'
alias gitlog='git log --oneline --decorate --graph --branches'
alias fishconf='hx ~/.config/fish/config.fish; source ~/.config/fish/config.fish'
alias i3conf='hx ~/.config/i3/config; i3-msg reload'
alias tmuxconf='hx ~/.tmux.conf; tmux source-file ~/.tmux.conf'
alias xmodmapconf='hx ~/.xmodmap; xmodmap ~/.xmodmap'
alias alacrittyconf='hx ~/.config/alacritty/alacritty.toml'
alias sourceall='xmodmap ~/.xmodmap; tmux source-file ~/.tmux.conf; source ~/.config/fish/config.fish'
alias syncconfs='sync-dotfiles'
alias gpuwatch='watch -n1 "cat /sys/class/drm/card0/device/hwmon/hwmon2/power1_average"'
alias blender_safe='hsa_disable_sdma=1 hip_visible_devices=0 rocr_visible_devices=0 /home/redbeard/apps/blender/blender --factory-startup'
alias notes='cd ~/pirate/notes; cargo run todos.md patterns.ron; hx todos.md knowledge.md'
alias promptnotes='hx ~/pl/qwertus/promptnotes.md'
alias catpromptnotes='cat ~/pl/qwertus/promptnotes.md'
alias geppetto='firefox chatgpt.com'
alias files='spf'
alias dolphin='spf'
alias music='spotify_player'
alias clock='peaclock'
alias music_visualizer='cava'
alias starwars='telnet towel.blinkenlights.nl'
alias retro='~/apps/cool-retro-term/cool-retro-term'
alias map='jp2a --colors -z --fill /usr/share/hollywood/map.jpg'
alias steamapps='awk -F\" '\''/"appid"/{appid=$4} /"name"/{print appid " - " $4}'\'' ~/.steam/steam/steamapps/appmanifest_*.acf'
alias steamconsole='steam steam://open/console/'
alias capstoggle='xdotool key Caps_Lock'
alias dudidadaa='paplay ~/Music/trash_jazz.wav'
alias gotofile='z (dirname (fzf))'
alias calc='qalc'
alias print='lp -d HP_Color_LaserJet_MFP_M283fdw_D03F69'

alias git_brains_sync="~/pirate/scripts/sync-mary-brains.fish"

set -x HELIX_RUNTIME /home/redbeard/.config/helix/runtime
set -x SCENERY_LIB_PATH /home/redbeard/pl/scenery/lib
set -x XDG_DATA_HOME /home/redbeard/.local/share/
set -x XDG_CONFIG_HOME /home/redbeard/.config/
set -x EDITOR hx
set -x ROCM_PATH /opt/rocm-6.4.3/rocm/rocm-6.4.3
set -x HIP_PATH $rocm_path
set -x LD_LIBRARY_PATH $rocm_path/lib $rocm_path/lib64 $rocm_path/llvm/lib
set -x PKG_CONFIG_PATH /usr/local/lib/pkgconfig:/usr/lib/pkgconfig

set fish_color_cwd magenta
set fish_color_user magenta

set -x SANE_DEFAULT_DEVICE "airscan:e0:HP Color LaserJet MFP M283fdw (D03F69)"

fish_add_path /usr/local/bin
fish_add_path /usr/bin
fish_add_path /bin
fish_add_path /home/redbeard/bin
fish_add_path /home/redbeard/.cargo/bin
fish_add_path /home/redbeard/.local/bin
fish_add_path /home/redbeard/scripts
fish_add_path /home/redbeard/apps/cool-retro-term
fish_add_path /usr/games
fish_add_path /home/redbeard/.opencode/bin

function sync-dotfiles
    set repo /home/redbeard/pirate/dotfiles

    cp /home/redbeard/.config/fish/config.fish $repo/config.fish
    cp /home/redbeard/.config/alacritty/alacritty.toml $repo/alacritty.toml
    cp /home/redbeard/.config/helix/config.toml $repo/config.toml
    cp /home/redbeard/.tmux.conf $repo/.tmux.conf
    cp /home/redbeard/.xmodmap $repo/.xmodmap

    mkdir -p $repo/.config/i3 $repo/.config/i3status $repo/.local/bin
    cp /home/redbeard/.config/i3/config $repo/.config/i3/config
    cp /home/redbeard/.config/i3status/config $repo/.config/i3status/config
    cp /home/redbeard/.local/bin/codex-tmux $repo/.local/bin/codex-tmux
    cp /home/redbeard/.local/bin/i3status-codex $repo/.local/bin/i3status-codex
    cp /home/redbeard/.local/bin/codex-rate-limits-json $repo/.local/bin/codex-rate-limits-json
    cp /home/redbeard/.local/bin/set-gtk-scaling $repo/.local/bin/set-gtk-scaling
    cp /home/redbeard/.local/bin/fix-pointer-accel $repo/.local/bin/fix-pointer-accel
    cp /home/redbeard/.local/bin/macbook-brightness $repo/.local/bin/macbook-brightness
    cp /home/redbeard/.local/bin/macbook-keyboard-brightness $repo/.local/bin/macbook-keyboard-brightness
    cp /home/redbeard/.local/bin/setup-displays.sh $repo/.local/bin/setup-displays.sh

    git -C $repo status --short
end

if status is-interactive
    setxkbmap us -variant altgr-intl
    xmodmap ~/.xmodmap
    tmux source-file ~/.tmux.conf

    fish_vi_key_bindings
end

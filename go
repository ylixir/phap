#!/usr/bin/env bash

nixProfile="$HOME/.nix-profile/etc/profile.d/nix.sh"

source "$nixProfile"

#####################
## dev environment ##
#####################

function setup-nix {
    if [ -x "$(command -v nix)" ]; then
        echo "Nix is already installed."
        echo "You may update it with the following commands:"
        echo "  $ nix-channel --update"
        echo "  $ nix-env -u"
    else
        function gpg_fake {
            echo "Faking GPG verification"
            true
        }

        if [ -x "$(command -v gpg2)" ]; then
            gpg=gpg2
        else
            echo "WARNING!! GPG not installed, cannot verify authenticity of nix!"
            echo "Press ^C to cancel, or any other key to continue."
            read -n 1 -s
            gpg=gpg_fake
        fi

        mkdir -p nixtmp
        curl -o nixtmp/install-nix https://nixos.org/nix/install \
        && curl -o nixtmp/install-nix.sig https://nixos.org/nix/install.sig \
        && $gpg --recv-keys B541D55301270E0BCF15CA5D8170B4726D7198DE \
        && $gpg --verify nixtmp/install-nix.sig \
        && sh nixtmp/install-nix
        rm -rf nixtmp
    fi
}

#execute a shell with debugging enabled
function debug {
    if [ $# -eq 0 ]
    then
        nix run -f nix/default.nix
    else
        nix run -f nix/default.nix -c "${@}"
    fi
}

#execute a shell with debugging disabled
function run {
    if [ $# -eq 0 ]
    then
        nix run -f nix/fast.nix
    else
        nix run -f nix/fast.nix -c "${@}"
    fi
}

function grep {
    run grep "${@}"
}

function sed {
    run sed "${@}"
}


########################
## project management ##
########################


function init {
    setup-nix
    source "$nixProfile"
    run composer install -n --prefer-dist
    run yarn install
}

function version {
    grep version composer.json | sed "s/^[^0-9]*\([0-9.]*\).*$/v\\1/g"
}
function tagged {
    git tag --merged master | grep $(version) > /dev/null
}

function tag {
    tagged || git tag $(version)
    git push origin $(version)
}


#####################
## quality control ##
#####################


prettierFiles='**/*.{json,md,php,yml,yaml}'
function format-verify {
    run yarn --silent prettier --list-different "$prettierFiles"
}

function format {
    run yarn --silent prettier --write "$prettierFiles"
}

function strict-types {
    local files="$(grep -rL 'declare(strict_types[ ]*=[ ]*1)' --include '*.php' "${@}" 2>/dev/null)"
    local lineCount="$(echo "$files" | sed '/^$/d' | wc -l)"
    if [ "0" -eq "$lineCount" ]
    then
        return 0
    else
        echo "The following files need 'declare(strict_types=1)'"
        echo "$files"
        return 1
    fi
}

function lint {
    run ./vendor/bin/psalm --threads=4 "${@}"
}

function test-debug {
    debug ./vendor/bin/phpunit "${@}"
}
function test {
    local project=$(pwd)/
    set -o pipefail
    run ./vendor/bin/phpunit "${@}" | sed "s:$project::g"
}

function check {
    test \
    && lint \
    && format \
    && strict-types tests src
}

function jekyll {
    run jekyll serve
}

$1 ${@:2}
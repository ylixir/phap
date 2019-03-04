#https://stesie.github.io/2016/08/nixos-github-pages-env
let pkgs=import <nixpkgs> {};
in
pkgs.bundlerEnv rec {
  name="jekyll_env";
  ruby=pkgs.ruby;
  gemfile=./Gemfile;
  lockfile=./Gemfile.lock;
  gemset=./gemset.nix;
}
let
pkgs = import <nixpkgs> {};
jekyll_env = import jekyll/jekyll.nix;
in
  with pkgs; [
    php
    php72Packages.composer
    yarn
    unzip
    cacert
    gnugrep
    gnused
    jekyll_env
  ]

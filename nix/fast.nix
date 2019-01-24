let
pkgs = import <nixpkgs> {};
in
  with pkgs; [
    php
    php72Packages.composer
    yarn
    unzip
    cacert
    gnugrep
    gnused
  ]

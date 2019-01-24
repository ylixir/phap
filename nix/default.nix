let
pkgs = import <nixpkgs> {};
phpdebug = import ./phpdebug/phpdebug.nix (with pkgs;
    { inherit
        makeWrapper
        symlinkJoin
        writeText
        ;
      php = pkgs.php;
      xdebug = pkgs.php72Packages.xdebug;
    });
in
  with pkgs; [
    phpdebug
    php72Packages.composer
    php72Packages.xdebug
    yarn
    unzip
    cacert
    gnugrep
    gnused
  ]



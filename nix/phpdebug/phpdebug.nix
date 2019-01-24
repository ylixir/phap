let
pkgs = import <nixpkgs> {};
in
{ php, xdebug, writeText, symlinkJoin, makeWrapper }:
symlinkJoin {
  name = "php";
  buildInputs = [makeWrapper];
  paths = [ php ];
  postBuild = ''
    wrapProgram "$out/bin/php" \
    --add-flags "-c ${./php.ini} -d zend_extension=${xdebug}/lib/php/extensions/xdebug.so"
  '';
}

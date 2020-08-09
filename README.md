quassellog - a very simple quassel log viewer

This is a php project to view log files on a quassel server via a web browser.

License:
--------
This source code is public domain. Do whatever you like with it

Requirements:
-------------
This needs to be installed on the server running quasselcore.

Only SQLite databases are supported.

Requires PHP (PHP 7 is the only version tested).

Installation:
-------------
Copy all files into a directory accessible to a web server running on the quasselcore host machine.

Edit index.php and change $sqlitedir (default is 'sqlite:/var/lib/quassel/quassel-storage.sqlite') to match your setup.

You may get a better experience if you turn off buffered output on your web server and php for the directory you run this from.
Note: you may need to disable deflate or gzip on your web server for this directory to disable the buffered output.

Log in using your quassel username and password

See the screenshots

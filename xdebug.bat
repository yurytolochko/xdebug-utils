@echo off

@setlocal

set PHP_XDEBUG_PATH=%~dp0

if "%PHP_COMMAND%" == "" set PHP_COMMAND=php.exe

"%PHP_COMMAND%" "%PHP_XDEBUG_PATH%xdebug" %*

@endlocal
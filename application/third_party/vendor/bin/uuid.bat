@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../rhumsaa/uuid/bin/uuid
php "%BIN_TARGET%" %*

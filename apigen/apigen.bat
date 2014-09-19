@echo off
REM ApiGen 2.8.0 - API documentation generator for PHP 5.3+
REM
REM Copyright (c) 2010-2011 David Grudl (http://davidgrudl.com)
REM Copyright (c) 2011-2012 Jaroslav Hanslík (https://github.com/kukulich)
REM Copyright (c) 2011-2012 Ondřej Nešpor (https://github.com/Andrewsville)
REM
REM For the full copyright and license information, please view
REM the file LICENCE.md that was distributed with this source code.
REM

IF EXIST "@php_bin@" (
	"@php_bin@" "@bin_dir@\apigen" %*
) ELSE (
	"php.exe" "%~dp0apigen.php" %*
)

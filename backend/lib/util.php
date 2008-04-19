<?php
/*
    Psych Desktop
    Copyright (C) 2006 Psychcf

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/
error_reporting(0);
//get rid of magicquotes
if (get_magic_quotes_gpc())
{
	foreach($_POST as $key => $value)
	{
		$_POST[$key] = stripslashes($value);
	}
}
if (get_magic_quotes_gpc())
{
	foreach($_GET as $key => $value)
	{
		$_GET[$key] = stripslashes($value);
	}
}

//sessions and cookies
function get_basepath() {
	$curpath = explode("/", $_SERVER['REQUEST_URI']);
	$dir = $GLOBALS['installing'] ? "install" : ($GLOBALS['mobile'] ? "mobile" : "backend");
	while($curpath[count($curpath)-1] != $dir) {
		if(count($curpath) == 0) return "/";
		array_pop($curpath);
	}
	array_pop($curpath);
	return implode("/", $curpath) . "/";
}
session_set_cookie_params(60*60*24*365, get_basepath());
session_name("desktop_session");
session_start();

//for debugging
function desktop_errorHandler($exception) {
	internal_error("generic_err", $exception->getMessage());
}
set_exception_handler("desktop_errorHandler");

//util functions
function internal_error($type, $msg="")
{
	if($msg=="") $msg = $type;
	header('FirePHP-Data: {"msg":"' . addslashes($msg) . '"}');
	$p = new intOutput();
	$p->set($type);
	error_log("Lucid Error: " . $type . " (" . $msg . ")");
	die();
}

function import($module) {
	$module = explode(".", $module);
	$path = implode(DIRECTORY_SEPARATOR, $module);
	$file = $GLOBALS['path'] . $path . ".php";
	return @include_once($file);
}
<?php
require("../lib/includes.php");
import("models.config");
import("models.user");
if($_GET['section'] == "stream")
{
	if($_GET['action'] == "save")
	{
		$p = $User->get_current();
		$result = $Config->filter("userid", $p->id);
		if(!isset($result[0])) { $u = new $Config(); $u->userid = $p->id; }
		else { $u = $result[0]; }
		$u->value = $_POST['value'];
		$u->save();
		$out = new intOutput();
		$out->set("ok");
	}
	if($_GET['action'] == "load")
	{
		$p = $User->get_current();
		$result = $Config->filter("userid", $p->id);
		$result = $result[0];
		echo $result->value;
	}
}
?>

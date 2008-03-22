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
	require("../lib/includes.php");
	import("models.app");
	import("models.user");
    if($_GET['section'] == "install")
	{
		$cur = $User->get_current();
		if($_GET['action'] == "package" && $cur->has_permission("api.ide"))
		{
			$out = new textareaOutput();	
			if(isset($_FILES['uploadedfile']['name'])) {
				import("lib.Archive");
				import("lib.xml");
				$xml = new Xml;
				File_Archive::extract(
					File_Archive::readUploadedFile("uploadedfile"),
					File_Archive::toFiles("../apps/tmp/".$_FILES['uploadedfile']['name'])
				);
				$in = $xml->parse("../apps/tmp/".$_FILES['uploadedfile']['name']."/appmeta.xml", 'FILE');
				$app = new $App();
				$app->name = $in[name];
				$app->author = $in[author];
				$app->email = $in[email];
				$app->version = $in[version];
				$app->maturity = $in[maturity];
				$app->category = $in[category];
				$app->filetypes = Zend_Json::decode($in['filetypes'] ? $in['filetypes'] : "[]");
				$installfile = $in[installFile];
				$templine = '';
				$file2 = fopen("../apps/tmp/".$_FILES['uploadedfile']['name'].$installfile, "r");
				while(!feof($file2)) {
					$templine = $templine . fgets($file2, 4096);
				}
				fclose ($file2); 
				$app->code = $templine;
				$app->save();
				$out->append("status", "success");
				rmdir("../apps/tmp/".$_FILES['uploadedfile']['name']);
			} else { $out->append("error", "No File Uploaded"); }
		}
	}
    if($_GET['section'] == "fetch")
	{
		if($_GET['action'] == "id")
		{
			$appname = $_POST["name"];
			$p = $App->filter("name", $appname);
			$p = $p[0];
			$out = new jsonOutput();
			$out->append("appid", $p->id);
		}
		if($_GET['action'] == "full")
		{
			header("Content-type: text/json");
			$p = $App->get($_POST['id']);
			echo $p->make_json();
		}
		if($_GET['action'] == "list")
		{
			$p = $App->all();
			$out = new jsonOutput();
			$list = array();
			foreach($p as $d => $v)
			{
				$item = array();
				foreach(array("id", "name", "author", "email", "maturity", "category", "version", "filetypes") as $key) {
					$item[$key] = $v->$key;
				}
				array_push($list, $item);
			}
			$out->set($list);
		}
	}
?>

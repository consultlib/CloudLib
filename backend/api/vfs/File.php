<?php
import("models.user");
class FileFs extends BaseFs {
	var $_username;
	function _startup() {
		global $User;
		$cur = $User->get_current();
		$this->_username = $cur->username;
	}
	function _basePath($path=false) {
		return $GLOBALS['path'] . "/../files/".$this->_username . "/" . ($path ? $path : "");
	}
	function _getRealPath($path) {
		return $this->_basePath($path);
	}
	function _getFileInfo($file, $realPath=false) {
		$r = array();
		$r['path'] = $file; //TODO: this is it's real path, get it's vfs path?
		$f = ($realPath ? "" : $this->_basePath()) . $file;
		$r['name'] = basename($f);
		if(is_dir($f)) {
			$r["type"] = "text/directory";
		}
		else if(is_file($f)) {
			$r["modified"] = date ("F d Y H:i:s.", filemtime($f));
			$r["size"] = filesize($f);
			$r["type"] = mime_content_type($f);
			//TODO: guess mimetype based on extension?
		}
		//get ID3 info if available
		if(function_exists("id3_get_tag")) {
			$id3 = id3_get_tag($f);
			foreach($id3 as $key=>$value) {
				$r["id3".str_replace(" ", "", ucwords($key))] = $value;
			}
		}
		return $r;
	}
	function _listPath($path) {
	    $dir = opendir($this->_basePath($path));
		if(!$dir){
			return false;
		} else {
			$arr = array();
			while(($file = readdir($dir)) !== false){
				if($file == '..' || $file == '.'){
					continue;
				} else {
					array_push($arr, $this->_getFileInfo($this->_basePath($path . "/" . $file), true));
				}
			}
			return $arr;
		}
	}
	function _remove($path) {
		$path = $this->_basePath($path);
		return $this->_deltree($path);
	}
	function _deltree( $f ){
	    if( is_dir( $f ) ){
	        foreach( scandir( $f ) as $item ){
	            if( !strcmp( $item, '.' ) || !strcmp( $item, '..' ) )
	                continue;       
	            $this->_deltree( $f . "/" . $item );
	        }   
	        return rmdir( $f );
	    }
	    else{
	        return unlink( $f );
	    }
	}
	function _createDirectory($path) {
		$path = $this->_basePath($path);
		return mkdir($path);
	}
	function _copy($source, $destination) {
		$source = $this->_basePath($source);
		$destination = $this->_basePath($destination);
		return copy($source, $destination);
	}
	function _rename($oldpath, $newpath) {
		$oldpath = $this->_basePath($oldpath);
		$newpath = $this->_basePath($newpath);
		return rename($oldpath, $newpath);
	}
	function _read($path) {
		$path = $this->_basePath($path);
		return file_get_contents($path);
	}
	function _write($path, $content) {
		$path = $this->_basePath($path);
		return file_put_contents($path, $content);
	}
}
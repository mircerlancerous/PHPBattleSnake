<?php
class Utilities{
	public static function UID($length=8,$numeric=FALSE){
		if($numeric){
			$max = "1";
			for($i=0; $i<$length; $i++){
				$max .= "0";
			}
			$max -= 1;
			$uid = rand(1,$max);
			while(strlen($uid) < $length){
				$uid = "0".$uid;
			}
			return $uid;
		}
		$bytes = openssl_random_pseudo_bytes($length);
		$bytes = md5($bytes);
		$num = mt_rand(0,32-$length);		//md5 is 32 in length
		return substr($bytes,$num,$length);
	}
	
	public static function AutoInclude($path,$ext="php"){
		$fileArr = array();
		$files = scandir($path);
		foreach($files as $file){
			if($file == '.' || $file == '..'){
				continue;
			}
			$fileName = $file;
			$file = $path.$file;
			if(is_dir($file)){
				$fileArr = AutoInclude($file."/",$ext);
			}
			else{
				$pos = strrpos($file,".");
				if($pos !== FALSE){
					//does extension match
					if(substr($file,$pos+1) != $ext){
						continue;
					}
				}
				elseif($ext !== ''){
					continue;
				}
				include_once($file);
				//echo "<br/>$file";
			}
		}
	}
	
	public static function InitModel(&$obj,$data,$removeUnset=TRUE){
		$setKeys = array();
		if(!$data){
			//do nothing
			return;
		}
		else if(is_array($data)){
			foreach($data as $key => $value){
				if(property_exists($obj,$key)){
					$obj->$key = $value;
					$setKeys[] = $key;
				}
			}
		}
		else if(is_object($data)){
			$list = get_object_vars($data);
			foreach($list as $key => $value){
				if(property_exists($obj,$key)){
					$obj->$key = $data->$key;
					$setKeys[] = $key;
				}
			}
		}
		if($removeUnset){
			//unset unset properties so that NULLs are not saved when not desired
			$list = get_object_vars($obj);
			foreach($list as $key => $value){
				if(!in_array($key,$setKeys)){
					unset($obj->$key);
				}
			}
		}
	}
}
?>
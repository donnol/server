<?php

	date_default_timezone_set('asia/shanghai');

	function getMillisecond() { 
		list($s1, $s2) = explode(' ', microtime()); 
		return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000); 
	}

	function writeSock($fp){
		$http = "GET / HTTP/1.1\n";    
		$http .= "Host: www.baidu.com\n";    
		$http .= "Connection: close\n\n";

		while(true){
			$size = strlen($http);

			$write = fwrite($fp,$http);		//检查返回值
			if(  $write === false ){
				continue;
			}else if( $write < $size ){
				$http = substr($http, $write, $size - $write);
			}else{
				break;
			}
		}
	}

	function sock($isBlock){
		$fp = fsockopen("www.qq.com", 80, $errno, $errstr, 30);
		if ( ! $fp )
			die('error fsockopen');

		if( ! stream_set_blocking($fp, $isBlock) )
			die('error set block');

		return $fp;						//fp -> resource() of type (stream)
	}

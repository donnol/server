<?php

	date_default_timezone_set('asia/shanghai');

	function getMillisecond() { 
		list($s1, $s2) = explode(' ', microtime()); 
		return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000); 
	}

	function sock($isBlock, $host){
		$fp = fsockopen($host, 80, $errno, $errstr, 30);
		if ( ! $fp )
			die('fsockopen error');

		if( ! stream_set_blocking($fp, $isBlock) )
			die('set blocking error');

		return $fp;						//fp -> resource() of type (stream)
	}

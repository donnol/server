<?php

	require_once(dirname(__FILE__)."/common.php"); 

	define('BLOCK', 1);

	function writeSock($fp, $host){
		$http = "GET / HTTP/1.1\n";    
		$http .= "Host: ".$host."\n";    
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

	function block($sockNum, $fp, $host){
		for( $i = 0; $i < $sockNum ; $i ++ ){
			$socket_client = sock(BLOCK, $host);

			if (!$socket_client) {
				die("$errstr ($errno)");
			} else {
				//$msg = " i am a super man! ";
				//$res = fwrite($socket_client, "$msg");

				writeSock($socket_client, $host);

				while( ! feof( $socket_client ) ){
					fwrite($fp, fread($socket_client, 2048));
				}
				fclose($socket_client);
			}
		}
	}

/*
   $fp = fopen('block_log.txt', 'wb');
   $sockNum = $argv[1];

   $blockTime = block($sockNum, $fp);

   fclose($fp);

   echo "sockNum -> ".$sockNum.chr(10);
   echo "	block -> "."filesize: ".filesize('block_log.txt').", block time:".$blockTime.chr(10);
*/

<?php

	require_once(dirname(__FILE__)."/common.php"); 

	define('BLOCK', 1);

	function block($sockNum, $fp){

		for( $i = 0; $i < $sockNum ; $i ++ ){
			$socket_client = sock(BLOCK);

			if (!$socket_client) {
				die("$errstr ($errno)");
			} else {
				//$msg = " i am a super man! ";
				//$res = fwrite($socket_client, "$msg");

				writeSock($socket_client);

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

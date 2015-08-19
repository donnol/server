<?php

	require_once(dirname(__FILE__)."/block.php");

	function forkBlock($sockNum,$fp, $host, $forkNum){

		$childs = array();
		for( $i = 0; $i < $forkNum ; $i ++ ){
			$pid = pcntl_fork();

			if( $pid == -1 ){
				die('could not fork');
			}else if( $pid == 0 ){
				block($sockNum/$forkNum, $fp, $host);
				exit(0);
			}else{
				$childs[] = $pid;
				continue;
			}
		}

		//echo 'child count of block -> '.count($childs).chr(10);

		while( count($childs) > 0 ) {
			foreach($childs as $key=>$pid) {
				$res = pcntl_waitpid($pid, $status, WNOHANG);

				// If the process has already exited
				if($res == -1 || $res > 0){
					unset($childs[$key]);
				}
			}

			//sleep(1);
		}

	}

/*
   $sockNum = $argv[1];
   $forkNum = $argv[2];

   $fileName = 'forkblock_log.txt';
   $fp = fopen($fileName, 'wb');

   $forkBlockTime = forkBlock($sockNum, $forkNum, $fp);

   fclose($fp);

   echo "sockNum -> ".$sockNum.chr(10);
   echo "fork => ".chr(10);
   echo "	block -> "."filesize: ".filesize($fileName).", block time:".$forkBlockTime.chr(10);
 */

<?php
	
	require_once(dirname(__FILE__)."/ev_nonblock.php");

	function forkEvNonblock($sockNum, $fp, $host, $forkNum){
		if( $sockNum % $forkNum != 0 )
			die("请输入整倍数".chr(10));

		$childs = array();
		for( $i = 0; $i < $forkNum; $i ++ ){
			$pid = pcntl_fork();

			if( $pid == -1 ){
				die('count not fork');
			}else if( $pid == 0 ){
				evNonblock($sockNum/$forkNum, $fp, $host);
				exit(0);
			}else{
				$childs[] = $pid;
				continue;
			}
		}

		//echo 'child count of nonblock -> '.count($childs).chr(10);

		while( count($childs) > 0 ) {
			foreach($childs as $key=>$pid) {
				$res = pcntl_waitpid($pid, $status, WNOHANG);

				// If the process has already exited
				if($res == -1 || $res > 0){
					unset($childs[$key]);
				}
			}

		}

	}

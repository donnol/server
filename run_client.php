<?php

require_once(dirname(__FILE__)."/fork_block.php");
require_once(dirname(__FILE__)."/fork_nonblock.php");
require_once(dirname(__FILE__)."/ev_nonblock.php");
require_once(dirname(__FILE__)."/fork_evnonblock.php");

/*
function run_block($sockNum, $forkNum){
	$beginTime =  
		$fileName = 'forkblock_log.txt';
	$fp_block = fopen($fileName, 'wb');
	$forkBlockTime = forkBlock($sockNum, $forkNum, $fp_block);
	fclose($fp_block);

	return array(
			'time'=>$forkBlockTime,
			'size'=>filesize($fileName),
			);
}

function run_nonblock($sockNum, $forkNum){
	$fileName = 'forknonblock_log.txt';
	$fp_nonblock = fopen($fileName, 'wb');
	$forkNonblockTime = forkNonblock($sockNum, $forkNum, $fp_nonblock);
	fclose($fp_nonblock);

	return array(
			'time'=>$forkNonblockTime,
			'size'=>filesize($fileName),
			);
}

function run_client($sockNum, $forkNum){
	$nonblock = run_nonblock($sockNum, $forkNum);
	$block = run_block($sockNum, $forkNum);

	$left = "=====   ->   ";
	$right = "   <-   =====";
	echo $left."sockNum -> ".$sockNum.$right.chr(10);
	echo $left."fork".$right.chr(10);
	echo $left."nonblock -> "."filesize: ".$nonblock['size'].", nonblock time: ".$nonblock['time'].$right.chr(10);
	echo $left."block -> "."filesize: ".$block['size'].", block time:".$block['time'].$right.chr(10);
}
*/

function run_client($sockNum, $host, $forkNum){	
	$task = array(
			//'block',
			//'forkBlock',
			//'nonblock',
			//'evNonblock',
			'forkNonBlock',
			'forkEvNonblock',
			);

	for( $i = 0 ; $i != count($task) ; $i++ ){
		$fileName = $task[$i].'_log.txt';
		$file = fopen( $fileName,'wb');
		$beginTime =  getMillisecond();
		
		call_user_func(
			$task[$i],
			$sockNum,
			$file,
			$host,
			$forkNum
		);

		$endTime =  getMillisecond();
		$time = $endTime - $beginTime;
		echo $task[$i]." -> ".filesize($fileName).'bytes, '.$time.'ms'.chr(10);
		sleep(1);
	}
}

run_client($argv[2], $argv[1], $argv[3]);

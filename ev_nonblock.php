<?php

	require_once(dirname(__FILE__)."/nonblock.php");

	$connections = array();
	$file = null;
	$base = null;
	$eventMap = array();

	function ev_nonblock($connections){
		ev_select($connections);
	}

	function ev_split($memory){

		$readFd = array_filter($memory,function($single){
			if( strlen($single['writeBuffer']) == 0 )
				return true;
			else
				return false;
		});
		$readFd = array_column($readFd,'fd');

		$writeFd = array_filter($memory,function($single){
			if( strlen($single['writeBuffer']) != 0 )
				return true;
			else
				return false;
		});
		$writeFd = array_column($writeFd,'fd');

		assert('count($readFd) != 0 || count($writeFd) != 0');

		return array(
			'readFd'=>$readFd,
			'writeFd'=>$writeFd,
		);
	}

	function ev_select($memory){
		global $base;
		//$splitFd = ev_split($memory);
		$readFd = array_map(function($single){
			return $single['fd'];
		},$memory);
		$writeFd = $readFd;
		//$readFd = $splitFd['readFd'];
		//$writeFd = $splitFd['writeFd'];

		/*$base = */ev_base();
		/*
		foreach( $readFd as $singleRead ){
			$event = ev_event($singleRead, 'read');
			if( $event === false )
				die('add event error'.chr(10));
		}
		*/
		foreach( $writeFd as $singleWrite ){
			$event = ev_event($singleWrite, 'write');
			if( $event === false )
				die('add event error'.chr(10));
		}
	
		ev_loop($base);
	}

	//每个时间绑定一个连接
	function ev_event(/*$base, */$socket, $mode){
		global $base;
		$event = event_new();
		if( $event === false )
			die('new event error'.chr(10));

		if( $mode == "read" ){
			$set = event_set($event, $socket, EV_READ , 'ev_read'/*, $base*/);
			if( $set === false )
				die('set event error'.chr(10));
		}else if( $mode == 'write' ){
			$set = event_set($event, $socket, EV_WRITE , 'ev_write'/*, $base*/);
			if( $set === false )
				die('set event error'.chr(10));
		}
		var_dump($socket);
		var_dump($event);
		var_dump($base);
		$baseSet = event_base_set($event, $base);
		if( $baseSet === false )
			die('set event base error'.chr(10));

		$eventAdd = event_add($event);
		if( $eventAdd === false )
			die('add event error'.chr(10));

		$eventMap[$socket] = $event;
		return $event;
	}

	function ev_base(){
		global $base;
		$base = event_base_new();		//一个就好
		if( $base === false )
			die('new base error'.chr(10));

		return $base;
	}

	function ev_loop(/*$base*/){
		global $base;
		$flag = event_base_loop($base);		//一个就好
		if( $flag == 0 ){
			echo 'loop success'.chr(10);
		}else if( $flag == -1 ){
			die('loop error'.chr(10));
		}else{
			echo 'no event registered'.chr(10);
		}
	}

	function ev_read($socket, $flag/*, $base*/){
		//check read
		global $base;
		var_dump("ev_read");
		global $connections;
		global $file;
		$index = null;
		foreach( $connections as $i=>$singleMemory ){
			if( $singleMemory['fd'] != $socket )
				continue;
			$index = $i;
			break;
		}
		assert('$index !== null');

		//获得数据
		$data = fread($socket,1024);
		var_dump($data);
		$connections['readBuffer'] .= $data;

		//关闭资源
		if( strlen($data) == 0 ){
			fwrite($file, $connections[$index]['readBuffer']);
			fclose($socket);
			unset($connections[$index]);
		}
	}

	function ev_write($socket, $flag/*, $base*/){
		//check write
		var_dump("write");
		global $connections;
		global $base;
		$index = null;
		foreach( $connections as $i=>$singleMemory ){
			if( $singleMemory['fd'] != $socket )
				continue;
			$index = $i;
			break;
		}
		assert('$index !== null');

		//写入
		$haveWrite = fwrite($socket,$connections[$index]['writeBuffer']);
		$strlength = strlen($connections[$index]['writeBuffer']);
		$connections[$index]['writeBuffer'] = substr( $connections[$index]['writeBuffer'] , $haveWrite );

		if( $haveWrite == $strlength ){
			$event = ev_event($socket, 'read');
			if( $event === false )
				die('add event error'.chr(10));

			//$data = fread($socket,1280);
			//var_dump($data);
		}else{
			$event = ev_event(/*$base,*/ $socket, 'write');
			if( $event === false )
				die('add event error'.chr(10));
		}
	}

	function run($host, $sockNum){
		$beginTime = getMillisecond();

		global $connections;
		global $file;

		$connections = sockFactory($sockNum, $host);

		$fileName = 'ev_nonblock.txt';
		$file = fopen($fileName, 'wb');

		ev_nonblock($connections);

		fclose($file);

		$endTime = getMillisecond();
		echo "ev_nonblock.txt => size: ".filesize($fileName).'bytes'.', time : '.($endTime - $beginTime).'ms'.chr(10);
	}

	run($argv[1], $argv[2]);

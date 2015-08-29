<?php

	require_once(dirname(__FILE__)."/nonblock.php");

	$connections = array();
	$file = null;
	$base = null;
	$eventMap = array();

	function ev_nonblock($connections){
		ev_select($connections);
	}

	function ev_select($memory){
		global $base;

		$readFd = array_map(function($single){
			return $single['fd'];
		},$memory);
		$writeFd = $readFd;

		ev_base();
		
		foreach( $readFd as $singleRead ){
			$event = ev_event($singleRead, 'read');
			if( $event === false )
				die('add event error'.chr(10));
		}

		foreach( $writeFd as $singleWrite ){
			$event = ev_event($singleWrite, 'write');
			if( $event === false )
				die('add event error'.chr(10));
		}
	
		ev_loop($base);
	}

	//每个时间绑定一个连接
	function ev_event($socket, $mode){
		global $base;
		global $eventMap;
		$event = event_new();
		if( $event === false )
			die('new event error'.chr(10));

		if( $mode == "read" ){
			$set = event_set($event, $socket, EV_READ , 'ev_read');
			if( $set === false )
				die('set event error'.chr(10));
		}else if( $mode == 'write' ){
			$set = event_set($event, $socket, EV_WRITE , 'ev_write');
			if( $set === false )
				die('set event error'.chr(10));
		}
		$baseSet = event_base_set($event, $base);
		if( $baseSet === false )
			die('set event base error'.chr(10));

		$eventAdd = event_add($event);
		if( $eventAdd === false )
			die('add event error'.chr(10));

		//$eventMap[$socket][$mode] = $event;		//将event保存到全局数组中，防止被gc回收；否则event会被删除,导致no event register
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

	function ev_loop(){
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
		global $eventMap;
		global $connections;
		global $file;

		//获得数据
		$data = fread($socket,1024);
		$connections[$socket]['readBuffer'] .= $data;

		//关闭资源
		if( strlen($data) == 0 ){
			$haveWrite = fwrite($file, $connections[$socket]['readBuffer']);
			fclose($socket);
			unset($connections[$socket]);
			//unset($eventMap[$socket]);
		}else{
			$event = ev_event($socket, 'read');
			if( $event === false )
				die('add event error'.chr(10));
		}
	}

	function ev_write($socket, $flag){
		//check write
		$beginTime = getMillisecond();

		global $connections;
		global $base;

		//写入
		$writelength = strlen($connections[$socket]['writeBuffer']);
		$haveWrite = fwrite($socket,$connections[$socket]['writeBuffer']);
		$connections[$socket]['writeBuffer'] = substr( $connections[$socket]['writeBuffer'] , $haveWrite );

		if( $haveWrite == $writelength ){
			$event = ev_event($socket, 'read');
			if( $event === false )
				die('add event error'.chr(10));
		}else{
			$event = ev_event($socket, 'write');
			if( $event === false )
				die('add event error'.chr(10));
		}

		$endTime = getMillisecond();

		echo "write time => ".($endTime - $beginTime).chr(10);
	}

	function run_ev_nonblock($sockNum, $fd, $host){
		global $connections;
		global $file;
		global $eventMap;

		$connection = sockFactory($sockNum, $host);
		foreach( $connection as $key=>$value ){
			$connections[$value['fd']] = $value;
		}
		$file = $fd;

		ev_nonblock($connections);

		unset($eventMap);
	}

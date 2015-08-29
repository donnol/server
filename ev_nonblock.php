<?php

	require_once(dirname(__FILE__)."/nonblock.php");

	$file = null;
	$base = null;
	$connections = array();
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

		//新建base
		ev_base();
		
		//建立读事件
		foreach( $readFd as $singleRead ){
			$event = ev_event($singleRead, 'read');
			if( $event === false )
				die('add event error'.chr(10));
		}

		//建立写事件
		foreach( $writeFd as $singleWrite ){
			$event = ev_event($singleWrite, 'write');
			if( $event === false )
				die('add event error'.chr(10));
		}
	
		//循环
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
			$set = event_set($event, $socket, EV_READ | EV_PERSIST, 'ev_read');		//事件一直持续，直到读完
			if( $set === false )
				die('set event error'.chr(10));
		}else if( $mode == "write" ){
			$set = event_set($event, $socket, EV_WRITE | EV_PERSIST, 'ev_write');	//事件一直持续，直到写完
			if( $set === false )
				die('set event error'.chr(10));
		}else{
			die('mode is wrong'.chr(10));
		}

		$baseSet = event_base_set($event, $base);
		if( $baseSet === false )
			die('set event base error'.chr(10));

		$eventAdd = event_add($event);
		if( $eventAdd === false )
			die('add event error'.chr(10));

		/*
		 * 将event保存到全局数组中，防止被gc自动回收；否则event会被删除,导致no event register
		 */
		$eventMap[$mode][$socket] = $event;

		return $event;
	}

	function ev_base(){
		global $base;

		$base = event_base_new();		//一个就好
		if( $base === false )
			die('new base error'.chr(10));
	}

	function ev_loop(){
		global $base;

		$flag = event_base_loop($base);		//一个就好
		if( $flag == 0 ){
			echo 'loop success'.chr(10);
		}else if( $flag == -1 ){
			die('loop error'.chr(10));
		}/*else{
			echo 'no event registered'.chr(10);
		}
		*/
	}

	function ev_read($socket, $flag){
		//check read
		global $base;
		global $connections;
		global $file;
		global $eventMap;

		//获得数据
		$data = fread($socket,2048);
		$connections[$socket]['readBuffer'] .= $data;

		//写入数据，关闭资源
		if( strlen($data) == 0 ){
			$haveWrite = fwrite($file, $connections[$socket]['readBuffer']);
			fclose($socket);
			unset($connections[$socket]);			//读完关闭连接，保存结果
			if( event_del($eventMap['read'][$socket]) === false )
				die('event delete error');
		}/*else{
			$event = ev_event($socket, 'read');		//未读完继续更新读状态
			if( $event === false )
				die('add event error'.chr(10));
		}
		*/
	}

	function ev_write($socket, $flag){
		//check write
		global $connections;
		global $base;
		global $eventMap;

		//写入 -- 可能分多次写
		$writelength = strlen($connections[$socket]['writeBuffer']);
		$haveWrite = fwrite($socket,$connections[$socket]['writeBuffer']);
		$connections[$socket]['writeBuffer'] = substr( $connections[$socket]['writeBuffer'] , $haveWrite );
		
		if( $haveWrite == $writelength ){
			if( event_del($eventMap['write'][$socket]) === false )
				die('event delete error');
			/*
			$event = ev_event($socket, 'read');			//写完更新为读状态
			if( $event === false )
				die('add event error'.chr(10));
			*/
		}/*else{
			$event = ev_event($socket, 'write');		//未写完继续更新写状态
			if( $event === false )
				die('add event error'.chr(10));
		}
		*/
	}

	function evNonblock($sockNum, $fd, $host){
		global $file;
		global $base;
		global $eventMap;
		global $connections;

		$file = $fd;
		$connection = sockFactory($sockNum, $host);
		foreach( $connection as $key=>$value ){
			$connections[$value['fd']] = $value;
		}

		ev_nonblock($connections);

		unset($eventMap);
		//event_base_free($base);
	}

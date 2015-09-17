<?php

	require_once(dirname(__FILE__)."/nonblock.php");

	$file = null;
	$base = null;
	$connections = array();
	$eventMap = array();
	$beventMap = array();

	function evConnect($connections){
		evSelect($connections);
	}

	function evBase(){
		global $base;

		$base = event_base_new();		//一个
		if( $base === false )
			die('new base error'.chr(10));
	}

	function evLoop(){
		global $base;

		$flag = event_base_loop($base);		//一个
		if( $flag == 0 ){
			echo 'loop success'.chr(10);
		}else if( $flag == -1 ){
			die('loop error'.chr(10));
		}/*else{
			echo 'no event registered'.chr(10);
		}
		*/
	}

	function evSelect($memory){
		global $base;

		$readFd = array_map(function($single){
			return $single['fd'];
		},$memory);
		$writeFd = $readFd;

		//新建base
		evBase();
		
		//建立事件
		foreach( $readFd as $singleRead ){
			$event = evEvent($singleRead);
			if( $event === false )
				die('add event error'.chr(10));
		}
	
		//循环
		evLoop($base);
	}

	//每个时间绑定一个连接
	function evEvent($socket){
		global $base;
		global $eventMap;

		$event = event_new();
		if( $event === false )
			die('new event error'.chr(10));

		$set = event_set($event, $socket, EV_READ | EV_WRITE | EV_PERSIST, 'evBuffer');		//事件一直持续，直到结束
		if( $set === false )
			die('set event error'.chr(10));

		$baseSet = event_base_set($event, $base);
		if( $baseSet === false )
			die('set event base error'.chr(10));

		$eventAdd = event_add($event);
		if( $eventAdd === false )
			die('add event error'.chr(10));

		/*
		 * 将event保存到全局数组中，防止被gc自动回收；否则event会被删除,导致no event register
		 */
		$eventMap[$socket] = $event;

		return $event;
	}

	function evBuffer($socket){
		global $base;
		global $beventMap;

		$buffer = event_buffer_new($socket, 'evRead', 'evWrite', 'evError', $socket);
		if( $buffer == false )
			die("buffer new failed".chr(10));

		$flag = event_buffer_base_set($buffer, $base);
		if( $flag == false )
			die("buffer base set error!".chr(10));

		event_buffer_timeout_set($buffer, 30, 30);
        event_buffer_watermark_set($buffer, EV_READ, 0, 0xffffff);  

		/*
        $priority = event_buffer_priority_set($buffer, 60); 
		if( $priority == false )
			die("priority set failed".chr(10));
		 */

        $enable = event_buffer_enable($buffer, EV_WRITE | EV_READ);
		if( $enable == false )
			die("enable set failed".chr(10));
	
		$beventMap[$buffer] = $buffer;
	}

	function evRead($buffer, $socket){
		//check read
		global $base;
		global $connections;
		global $file;
		global $eventMap;
		global $beventMap;
		var_dump('read ok');

		//获得数据
		//event_buffer_enable($beventMap[$socket]);
		//$data = fread($socket,2048);
		
		var_dump($buffer);
		var_dump($socket);
		//event_buffer_disable($buffer, EV_WRITE);
		while( $data = event_buffer_read($buffer, 2048) ){
			$connections[$socket]['readBuffer'] .= $data;
		}
		$haveWrite = fwrite($file, $connections[$socket]['readBuffer']);
		//event_buffer_disable($buffer, EV_READ);

		//写入数据，关闭资源
		/*
		if( strlen($data) == 0 ){
			$haveWrite = fwrite($file, $connections[$socket]['readBuffer']);
			fclose($socket);
			event_buffer_free($buffer);
			unset($connections[$socket]);			//读完关闭连接，保存结果
			if( event_del($eventMap[$socket]) === false )
				die('event delete error');
		}/*else{
			$event = evEvent($socket, 'read');		//未读完继续更新读状态
			if( $event === false )
				die('add event error'.chr(10));
		}
		*/
	}

	function evWrite($buffer, $socket){
		//check write
		global $connections;
		global $base;
		global $eventMap;
		global $beventMap;
		var_dump('write ok');
		var_dump($buffer);
		var_dump($socket);

		//写入 -- 可能分多次写
		//event_buffer_enable($beventMap[$socket]);
		//$writelength = strlen($connections[$socket]['writeBuffer']);
		//$haveWrite = fwrite($socket,$connections[$socket]['writeBuffer']);

		$dataLen = strlen($connections[$socket]['writeBuffer']); 
		$isWrite = event_buffer_write($buffer, $connections[$socket]['writeBuffer']);

		if( $isWrite == false )
			die('write failed');

		//event_buffer_enable($buffer, EV_READ | EV_PERSIST);

		/*
		$connections[$socket]['writeBuffer'] = substr( $connections[$socket]['writeBuffer'] , $haveWrite );
		
		if( $haveWrite == $writelength ){
			if( event_del($eventMap[$socket]) === false )
				die('event delete error');

			$event = evEvent($socket, 'read');			//写完更新为读状态
			if( $event === false )
				die('add event error'.chr(10));
		}else{
			$event = evEvent($socket, 'write');		//未写完继续更新写状态
			if( $event === false )
				die('add event error'.chr(10));
		}
		*/
	}

	function evError($buffer, $socket){
		//event_buffer_set_callback($buffer, 'evRead', 'evWrite', 'evError', $socket);
		event_buffer_disable($buffer, EV_READ | EV_WRITE);
		echo chr(10);
		var_dump($buffer);
		//socket 变成了int
		var_dump($socket);
		exit(0);
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

		evConnect($connections);

		unset($eventMap);
		unset($beventMap);
		//event_base_free($base);
	}

	$fileName = "buffer_ev_nonblock.txt";
	$fd = fopen($fileName, 'wb');
	evNonblock($argv[2], $fd, $argv[1]);
	fclose($fd);

	echo filesize($fileName).chr(10);


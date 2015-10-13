<?php

	function openSocket($host="127.0.0.1", $port=8000){
		$socket = stream_socket_server("tcp://".$host.":".$port, $errno, $errmsg,
										STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
		if( ! $socket )
			die("open socket $host : $port failed.".chr(10));

		stream_set_blocking($socket, 0);
		return $socket;
	}

	function accept(){
	
	}

	function read($socket, $bufSize=1024){
		$data = fread($socket, $bufSize);
		if( count($data) == 0 ){
			echo "no read data.\n";
			fclose($connections[$socket]);
			//continue;
		}
		echo $data;
	}

	function write($socket, $data=''){
		if( $data != '')
			fwrite($socket, $data);
		else
			fwrite($socket, "hello world!".chr(10));
	}

	function error(){
		die("error!".chr(10));
	}

	function main() {
		$socket = openSocket();

		$connections = array();
		while(true){
			$socketArray[$socket] = $socket;
			$readfds = array_merge($connections, $socketArray);
			$writefds = array();
			$error = null;

			if( stream_select($readfds, $writefds, $error, 10000) ){
				if( in_array($socket, $readfds) ){
					$newConn = stream_socket_accept($socket);
					$connections[$newConn] = $newConn;

					$reject = '';
					if( count($connections) >= 1024 )
						$reject = "Server full, try again later.\n";

					$writefds[$newConn] = $newConn;

					if( $reject != '' ){
						write($writefds[$newConn], $reject);
						unset($writefds[$newConn]);
						fclose($connections[$newConn]);
						unset($connections[$newConn]);
					}else{
						echo "Client $newConn come.\n";
					}

					//unset($readfds[$newConn]);
				}
				
				/*
				foreach( $readfds as $rfd ){
					echo "reading ...\n";
					read($rfd);
					unset($readfds[$rfd]);
				}
				 */

				foreach( $writefds as $wfd ){
					echo "writing ...\n";
					write($wfd);
				}
			}
		}
	}

	main();

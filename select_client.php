<?php

	require_once(dirname(__FILE__)."/nonblock.php");

	function main(){
		$file = fopen('select_client.txt', 'wb');
		$sockets = sockFactory(10, '127.0.0.1', 8000);

		$connections = array();
		foreach( $sockets as $single ){
			$connections[$single['fd']] = $single;
		}
		$readfds = array_column($connections, 'fd');
		$writefds = $readfds;
		$error = null;
		while( count($connections) ){
			if( stream_select($readfds, $writefds, $error, 10000) ){
				
				foreach( $readfds as $rfd ){
					//echo "reading ...\n";
					echo fread($rfd, 1024);
					unset($readfds[$rfd]);
				}

				foreach( $writefds as $key=>$wfd ){
					//echo "writing ...\n";
					echo "hi girl.\n";
					fwrite($wfd, "hi girl.\n");
					unset($writefds[$wfd]);
					unset($connections[$wfd]);
				}
			}
		}
		fclose($file);
	}

	main();

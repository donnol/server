<?php
	
	require_once(dirname(__FILE__)."/common.php");

	define('NONBLOCK', 0);

	function sockFactory($sockNum, $host){
		$memory = array();
		for( $i = 0 ; $i < $sockNum ; $i ++ ){
			$single = array(
				'fd'=>sock(NONBLOCK, $host),
				'writeBuffer'=>
					"GET / HTTP/1.1\n".
					"Host: ".$host."\n".
					"Connection:close\n\n",
				'readBuffer'=>''
			);
			$memory[] = $single;
		}
		return $memory;
	}

	function nonblock($sockNum, $fp, $host){
		$memory = sockFactory($sockNum, $host);
		$convenient_read_block=1024;
		$timeout = 30;

		$result = array();
		while(count($memory)){

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
				
			//准备就绪的资源返回到read中,可能是1或多个,阻塞时无用
			if( stream_select($readFd,$writeFd,$except,$timeout) === false ) {	
				echo "stream select failed ".chr(10);
			}else{
				//check read
				foreach($readFd as $r){
					$index = null;
					foreach( $memory as $i=>$singleMemory ){
						if( $singleMemory['fd'] != $r )
							continue;
						$index = $i;
						break;
					}
					assert('$index !== null');

					//获得数据
					$data = fread($r,$convenient_read_block);
					$memory[$index]['readBuffer'] .= $data;

					//关闭资源
					if( strlen($data) == 0 ){
						fwrite($fp, $memory[$index]['readBuffer']);
						fclose($r);
						unset($memory[$index]);
					}
				}
				//check write
				foreach( $writeFd as $w ){
					$index = null;
					foreach( $memory as $i=>$singleMemory ){
						if( $singleMemory['fd'] != $w )
							continue;
						$index = $i;
						break;
					}
					assert('$index !== null');
					
					//写入
					$haveWrite = fwrite($w,$memory[$index]['writeBuffer']);
					$memory[$index]['writeBuffer'] = substr( $memory[$index]['writeBuffer'] , $haveWrite );
				}
			}
		}
	}

/*
$fileName = 'nonblock_log.txt';
$fp = fopen($fileName, 'wb');
$sockNum = 100;

$nonblockTime = nonblock($sockNum, $fp);

fclose($fp);
echo "	nonblock -> "."filesize: ".filesize($fileName).", nonblock time: ".$nonblockTime.chr(10);
*/

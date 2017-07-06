<?php
namespace Sock;

require_once "SocketException.php";
require_once "SocketClient.php";

class SocketClientBroadcast extends SocketClient {
	
	protected $server;


	
	public function __construct( $connection, SocketServerBroadcast $server ) {
		parent::__construct( $connection );
		$this->server = $server;

		while(!$this->setName()){
			$this->setName();
		}

		$server->name = $this->getName();
	}
	
	public function sendBroadcast($message) {
		$this->server->broadcast( array( 'data' => $message."\n", 'type' => 'msg' ) );
	}
	
	public function disconnected() {
		$name = $this->getName();
		$this->server->broadcast( array( 'data' => $name . " has disconnected\n" , 'type' => 'msg' ) );
		$this->server->broadcast( array( 'type' => 'disc' ) );
		$this->close();
	}
	
	public function connected($name) {

		// don't need this file open in child processes
		unset($this->server->pipe);
		$this->server->broadcast( array( 'data' => $name . " has joined\n" , 'type' => 'msg' ) );

	}

}

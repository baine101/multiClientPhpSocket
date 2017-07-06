<?php
/**
 * Created by PhpStorm.
 * User: arkus
 * Date: 16/03/17
 * Time: 04:02
 */

namespace Sock;

class SocketClient {

	private $connection;
	private $address;
	private $port;
	private $name;
	public $nameSet;

	public function __construct( $connection ) {
		$address = '';
		$port    = '';
		// Gets the ip address and port from the client
		socket_getsockname( $connection, $address, $port );
		$this->address    = $address;
		$this->port       = $port;
		$this->connection = $connection;
	}



	/**
	 *  Sends a message to client
	 *
	 * @param $message
	 */
	public function send( $message ) {
		$new = $message;
		// Catch the error if it fails to write to the socket to a specific client : TODO : add the client (pid) number to error
		//try {
			socket_write( $this->connection, $new, strlen( $new ) );
		//} catch ( \Exception $e ) {
		//	printf( "Error", $e );
		//}
	}

	/**
	 *  Reads message from client
	 *
	 * @param int $len
	 *
	 * @return null|string
	 */
	public function read( $len = 1024 ) {
		if ( ( $buf = @socket_read( $this->connection, $len, PHP_BINARY_READ ) ) === false ) {
			return null;
		}
		return $buf;
	}

	/**
	 *  Sets the name of the client from user input
	 *
	 * @return bool
	 */
	public function setName() {



		// Get and set the name of the client
		$this->nameSet = false;

		while ( $this->nameSet == false ) {

			$this->send( "Enter a name: " );

			// Read the users input
			$readName = '';
			$readName = $this->read();
			// Remove whitespace, dots and carriage returns
			$readName = str_replace( array( '.', ' ', "\n", "\t", "\r" ), '', $readName );


			if ( $readName == '' ) {
				$this->send( "No value entered !\n" );
				return false;
			} else {
				$this->name = $readName;

				$this->send( "Your name is " . $this->name ."!\n" );
				$this->nameSet = true;
				return true;
			}
		}

		return $this->nameSet;

	}


	/**
	 *  Returns the name of client
	 *
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}

	/**
	 *  Returns IP address of client
	 *
	 * @return string
	 */
	public function getAddress() {
		return $this->address;
	}

	/**
	 *  Returns port of client
	 *
	 * @return string
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 *  Close's the socket
	 */
	public function close() {
		socket_shutdown( $this->connection );
		socket_close( $this->connection );
	}
}

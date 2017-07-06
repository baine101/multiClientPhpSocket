<?php
/**
 * Created by PhpStorm.
 * User: arkus
 * Date: 16/03/17
 * Time: 04:02
 */

namespace Sock;

require_once "SocketException.php";
require_once "SocketClient.php";

class SocketServer {

    protected $sockServer;
    public $name;
    protected $address;
    protected $port;
    protected $_listenLoop;
    protected $connectionHandler;





    public function __construct( $port = 51717) {
	    $host= gethostname();
	    $address = gethostbyname($host);

        $this->address = $address;
        $this->port = $port;
        $this->_listenLoop = false;
    }

    public function init() {
        $this->_createSocket();
        $this->_bindSocket();
    }

    /**
     * Creates the socket
     *
     * @throws SocketException
     */
    private function _createSocket() {
        $this->sockServer = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if( $this->sockServer === false ) {
            throw new SocketException(
                SocketException::CANT_CREATE_SOCKET,
                socket_strerror(socket_last_error()) );
        }

        socket_set_option($this->sockServer, SOL_SOCKET, SO_REUSEADDR, 1);
    }

    /**
     * Binds the local ip address and port to the socket
     *
     * @throws SocketException
     */
    private function _bindSocket() {
        if( socket_bind($this->sockServer, $this->address, $this->port) === false ) {
            throw new SocketException(
                SocketException::CANT_BIND_SOCKET,
                socket_strerror(socket_last_error( $this->sockServer ) ) );
        }
    }


    /**
     *  Runs a given function when called
     *
     * @param $handler
     */
    public function setConnectionHandler( $handler ) {
        $this->connectionHandler = $handler;
    }

    /**
     *  Listen for connections to the socket
     *
     * @throws SocketException
     */
    public function listen() {
        if( socket_listen($this->sockServer, 5) === false) {
            throw new SocketException(
                SocketException::CANT_LISTEN,
                socket_strerror(socket_last_error( $this->sockServer ) ) );
        }

        $this->_listenLoop = true;
        $this->beforeServerLoop();
        $this->serverLoop();

        socket_close( $this->sockServer );
    }

    /**
     *  Prints the ip address and port
     *  the server socket is using
     */
    protected function beforeServerLoop() {
        printf( "Listening on %s:%d...\n", $this->address, $this->port );
    }



    protected function serverLoop() {

        // While the socket is listening for connections
        while( $this->_listenLoop ) {

            /* If the client could not be accepted
           *  @socket_accept stops the original error from being made
           *  and instead we throw an exception
           */
            if( ( $client = @socket_accept( $this->sockServer ) ) === false ) {
                throw new SocketException(
                    SocketException::CANT_ACCEPT,
                    socket_strerror(socket_last_error( $this->sockServer ) ) );
                // Continue from loop
                continue;
            }

            // New instance of SocketClient with each client thats conected
            $socketClient = new SocketClient( $client );

            // Sorts Between object or property
            // and runs the connectionHandler
            if( is_array( $this->connectionHandler ) ) {
                $object = $this->connectionHandler[0];
                $method = $this->connectionHandler[1];
                $object->$method( $socketClient );
            }
            else {
                $function = $this->connectionHandler;
                $function( $socketClient );
            }
        }
    }

}


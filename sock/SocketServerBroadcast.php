<?php

namespace Sock;

require_once "SocketServer.php";
require_once "SocketClientBroadcast.php";
require_once "commands.php";

class SocketServerBroadcast extends SocketServer
{

    const PIPENAME = '/tmp/broadcastserver.pid';

    protected $pid;
    public $pipe;

    private $connections = array();

    public function __construct($port = 51717)
    {
	    $host= gethostname();
	    $address = gethostbyname($host);

	    $this->address = $address;

        parent::__construct($port, $address);
        $this->pid = posix_getpid();
        if (!file_exists(self::PIPENAME)) {
            umask(0);
            if (!posix_mkfifo(self::PIPENAME, 0666)) {
                die('Cant create a pipe: "' . self::PIPENAME . '"');
            }
        }
        $this->pipe = fopen(self::PIPENAME, 'r+');
    }

    public function handleProcess()
    {
        // Stores the first 4 bytes of the .pid .. the length of file in hex
        $header = fread($this->pipe, 4);
        // Converts the Hexadecimal to a Integer
        $len = $this->hexToInt($header);
        // Stores the full message as array
        $message = unserialize(fread($this->pipe, $len));

        // If the message type is a message
        if ($message['type'] == 'msg') {
        	// Get the client's object that sent the message using its pid
	        $client = $this->connections[ $message['pid'] ];


	        if(substr( $message['data'], 0, 2 ) == "!!" ){
		        // Remove the first 2 chars , whitespace, dots and carriage returns
		        $command = substr($message['data'], 2);
		        $command = str_replace( array( '.', ' ', "\n", "\t", "\r" ), '', $command );

				var_dump($command);
				$cmdClass = new commands($client);
				$cmdClass->runCmd($command);

	        }else {

		        // Build a string to print the clients IP , its pid , and the message text itself
		        $msg = sprintf( '[%s]: %s', $client->getName(), $message['data'] );

		        // Add some text to start of message
		        printf( "Broadcast: %s", $msg );

		        // Loop through each connection with a key of the pid and a value of the client
		        foreach ( $this->connections as $pid => $conn ) {

			        if ( $pid == $message['pid'] ) {
				        continue;
			        }

			        // Sends the message to the connection
			        $conn->send( $msg );
		        }
	        }
        // If the message type is a disconnect
        } elseif ($message['type'] == 'disc') {
            // Unset the pid from the connection array
            unset($this->connections[$message['pid']]);
        }
    }

    public function hexToInt($char)
    {
        $num = ord($char[0]);
        $num += ord($char[1]) << 8;
        $num += ord($char[2]) << 16;
        $num += ord($char[3]) << 24;
        return $num;
    }

    /**
     * Runs before server loop
     *
     */
    protected function beforeServerLoop()
    {
        parent::beforeServerLoop();
        // Sets the O_NONBLOCK flag on the socket
        socket_set_nonblock($this->sockServer);
        // Sets 'handleProcess' as the signal handler
        pcntl_signal(SIGUSR1, array($this, 'handleProcess'), true);
    }

    /**
     *  Main server loop
     */
    protected function serverLoop()
    {

        // While the socket is listening for connections
        while ($this->_listenLoop) {

           /* If the client could not be accepted
            *  @socket_accept stops the original error from being made
            *  and instead we throw an exception
            */
            if (($client = @socket_accept($this->sockServer)) === false) {

                $info = array();
                // If the timeout had been met and info contains data : SIGUSR1 = 10
                if (pcntl_sigtimedwait(array(SIGUSR1), $info, 1) > 0) {

                    // If signo = 10
                    if ($info['signo'] == SIGUSR1) {
                        // Handle the Process
                        $this->handleProcess();
                    }
                }
                continue;
            }

            // New instance of a broadcast client connection
            $socketClient = new SocketClientBroadcast($client, $this);


            if (is_array($this->connectionHandler)) {
                $object = $this->connectionHandler[0];
                $method = $this->connectionHandler[1];
                $childPid = $object->$method($socketClient);
            } else {
                // Stores the function name set by the $server->setConnectionHandler() in main script
                $function = $this->connectionHandler;
                // Stores the process id from running the $function with the client as a parameter
                $childPid = $function($socketClient);
            }

            // If no child process id is found
            if (!$childPid) {
                // Force child process to exit from loop
                return;
            }

            // Add the client too the connections array
            $this->connections[$childPid] = $socketClient;
            //var_dump($socketClient);
        }

    }

    public function broadcast(Array $msg)
    {



        $msg['pid'] = posix_getpid();
        $message = serialize($msg);

        $f = fopen(self::PIPENAME, 'w+');
        if (!$f) {
            echo "ERROR: Can't open PIPE for writing\n";
            return;
        }

        fwrite($f, $this->strlenInBytes($message) . $message);
        fclose($f);

        // Sends our defined signal callback to the child process
        posix_kill($this->pid, SIGUSR1);
    }

    public function getPid(){
    	return $this->pid;
    }

    protected function strlenInBytes($str)
    {
        $len = strlen($str);
        $chars = chr($len & 0xFF);
        $chars .= chr(($len >> 8) & 0xFF);
        $chars .= chr(($len >> 16) & 0xFF);
        $chars .= chr(($len >> 24) & 0xFF);
        return $chars;
    }
}

<?php
namespace sock;

class commands {

	protected $client;

	public function __construct($client) {
		$this->client = $client;
	}

	public function runCmd($cmd){
	 	switch ($cmd) {
		    case 'logout':
		    	    printf("[" . $this->client->getName(). "]" . "logout\n");
				    $this->client->disconect();
	        break;

		    case 'getname':
			    printf("[" . $this->client->getName(). "]" . "getname\n");
			    $this->client->send($this->client->getName());

			    break;

		    case 'uptime':
			    printf("[" . $this->client->getName(). "]" . "uptime\n");
			    exec( "uptime", $uptime ) ;
			    $this->client->send($uptime[0]."\n");

			    break;

		    case 'help':
			    printf("[" . $this->client->getName(). "]" . "help\n");
			    $this->client->send("!!name (not working)\n!!getname\n!!uptime\n!!help\n");

			    break;

		    default:
		    	$this->client->send("Cannot Find Command!\nPlease use !!help to see command list\n");
		    break;
	    }
	 }


}
<?php
namespace sock;

class commands {

	protected $client;

	public function __construct($client) {
		$this->client = $client;
	}

	public function runCmd($cmd){
	 	switch ($cmd) {
		    case 'name':
				    $this->client->setName();

	        break;

		    case 'uptime':


			break;

		    default:
		    	$this->client->send("Cannot Find Command!\n");
		    break;
	    }
	 }


}
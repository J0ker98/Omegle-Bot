<?php

# Written by J0ker98
## Lincensed under GPL v3.0

ini_set('default_socket_timeout',1);
class OmBot
{
// Variables
private $unique=null; // Omegle's Unique ID. Script sets this itself.
public $connected=false; // Is a stranger connected? This variable might come in handy for your bot.
public $newMessage=false; // Has there been a new message?
public $recentMsg=null; // Contents of last message.

public $name="Omegle Bot"; // Name of your bot. IMPORTANT

public $idle_timeout=false; // Do we want to disconnect if Stranger isn't talking?
public $idle_seconds=0; // How long to wait if stranger stops talking.  For use with $idle_timeout.
private $tO=0; // Variable used with the idle timeout.  Don't mess with it.
public $newConn; // Did the bot connect again?
public $retryLimit = 10; // If requests aren't going through to the server, how many times do we retry?
public $excessWait = 20; // How long to wait before reconnecting if Omegle thinks you're connecting too much.

// Functions
function conn() // Initial connection function.
{
$this->unique = eregi_replace('"',null,file_get_contents('http://omegle.com/start'));
if (!$this->unique){$this->conn();} // First connection attempt didn't go through, keep trying.
echo $this->name." has connected.\nUnique ID: $this->unique.\n";
$this->tO=0; // Reset this for a new connection.
$this->newConn = true; // Reconnected.
}

function dconn() // Disconnect from Omegle.
{
$this->gather_data("http://omegle.com/disconnect",$this->unique,null);
echo "$this->name disconnected.\n";
$this->connected = false; // We're not connected anymore.
}

function s_msg($msg) // Send messages.
{
$this->gather_data("http://www.omegle.com/send",$this->unique,$msg);
print chr(27).'[1;34m'.$this->name.':'.chr(27).'[0m'." $msg\n";
}

function ptype() // Fake typing.
{
echo "Pretending to type.\n";
$this->gather_data("http://omegle.com/typing",$this->unique,null);
}

function call_handler() // Calls the event handler.
{
	if ($this->idle_timeout) // This times out if Stranger stops talking.  Does nothing if idle_timeout is set to false.
	{
		if ($this->tO==$this->idle_seconds)
		{$this->dconn();echo "Stranger wasn't talking.\n";sleep(2);$this->conn();}
	}
	$events = $this->gather_data("http://omegle.com/events",$this->unique,null);
	//echo $events."\n";
	if ($events != "null") // If something actually happened.
	{$this->handle_it(json_decode($events)); // Call the event handler.
	}
	else{sleep(1);$this->tO=$this->tO+1;} // Otherwise, we're idle. Count the idle seconds.
}

function handle_it($evnt) // The event handler.
{
$this->tO=0; // Something actually happened, so reset this number.
foreach ($evnt as $argh)
{
	switch($argh[0])
	{
		case "connected":
		$this->connected = true;
		print "Stranger connected.\n";
		break;

		case "waiting":
		$this->connected = false;
		print "Waiting for Stranger.\n";
		break;

		case "typing":
		print "Stranger is typing.\n";
		break;

		case "gotMessage":
		if ($argh[1] == "Please reload the page for technical reasons.") // Omegle thinks we're connecting too much.
		{
			echo chr(27).'[1;31;40m'."Omegle is on to us!  Hiding under the covers.".chr(27).'[0m'."\n";
			sleep($this->excessWait); // Wait a while before reconnecting.
			$this->conn(); // Reconnect.
		}
		else // Actual person, not Omegle.
		{
			$this->newMessage = true; // If the bot wants to know we have a message.
			$this->recentMsg = $argh[1];
			echo chr(27).'[1;31m'."Stranger:".chr(27).'[0m'." $this->recentMsg\n";
		}
		break;

		case "strangerDisconnected":
		echo "Stranger disconnected.\n";
		sleep(2); // Don't reconnect right away.  Omegle doesn't like that.
		$this->connected = false;
		$this->newMessage = false;
		$this->conn();
		break;
	}
}
}

function gather_data($url,$id,$msg)  // This replaces the need for cURL.  Returns the contents of the URL.
{
$retries = $this->retryLimit;
$postarr = array('id' => $id,'msg' => $msg); // Variables to POST.
$pd = http_build_query($postarr);
$opts = array(
'http' => array(
 'method'  => 'POST',
 'header'  => 'Content-type: application/x-www-form-urlencoded',
 'content' => $pd,
));
$context  = stream_context_create($opts);
$result=null;
$try = 0; // Reset retries.
while($result==null && $try < $retries) // If it doesn't go through, keep trying.
{
usleep(151123);
$result = @file_get_contents($url, false, $context);
$try = $try + 1;
}
if (!$result){echo "Server timed out. Damn you, Omegle!\n";$tO=0; $this->conn();} // Still didn't work.
return $result; // Return the URL contents.
}
}
?>

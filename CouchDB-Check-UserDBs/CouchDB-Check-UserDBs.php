<?php


$COUCHURL = "http://";
$COUCHUSER = "admin";
$COUCHPASSWORD = "";
$startKey = "org.couchdb.user:SPZFNC81A42A509L";

if (file_exists("./config.php")) { include "./config.php"; }

###############
## General Shit

// Point to where you downloaded the phar
include('./httpful.phar');
function e($string) { echo $string.PHP_EOL; }

use Httpful\Request;
$template = Request::init()
			->authenticateWith($COUCHUSER, $COUCHPASSWORD);
Request::ini($template);

###############
## Proper stuff

e("");
e("CouchDB Check UserDB version 1.0");
e("https://github.com/skeyby/");
e("");


### Phase 1

e("[*] Tasting CouchDB at ".$COUCHURL);
$response = \Httpful\Request::get($COUCHURL)->send();

if (! isset($response->body->couchdb)) {
	e("[!] CouchDB not found");
	die();
}

e("[*] Found CouchDB version ".$response->body->version." (".$response->body->uuid.")");
e("");

### Phase 2

e("[*] Fetching users listing:");

$MaxUsers = 200000;

for ($Users = 0; $Users < $MaxUsers; $Users++) {
	$response = \Httpful\Request::get($COUCHURL.'/_users/_all_docs?startkey='.json_encode($startKey).'&limit=1')->send();

	if (! $response->body->rows[0]->id OR substr($response->body->rows[0]->id, 0, 16) != "org.couchdb.user") {
		e("[!] Unknown reply");
		die();
	}
	$WholeUser = $response->body->rows[0]->id;
	$UserSuffix = substr($WholeUser, 17);
	$UserDB = "userdb-".bin2hex($UserSuffix);

	e("[-] Found user: ".$WholeUser." -> ".$UserSuffix." -> ".$UserDB." (".($Users+1)."/".$MaxUsers.")");

	### Phase 3 - checking if userDB exists

	$response = \Httpful\Request::get($COUCHURL.'/'.$UserDB)->send();

	if ($response->body->db_name) {
		## DB Exists
		e("[---] Found DB: ".$response->body->db_name." -> ".$response->body->doc_count." documents -> ".$response->body->cluster->n." Nodes");
		$startKey = $WholeUser.".";
	} else {
		## DB Doesn't exists
		e("[!!!] DB not found:");

		### Phase 4 - creating the DB

		$response = \Httpful\Request::put($COUCHURL."/".$UserDB)
                        ->sendsJson()
                        ->send();

        if (isset($response->body->ok) && $response->body->ok == 1)
			e("[!!-] DB Created.");
		else {
			if ($response->code == 412) {
				e("[!!-] Bbbbboing...");
				sleep(3);
			} else {
				print_r($response->body);
				die("[!] Cannot create database");
			}
		}

        $DBSecurity = new StdClass();
        $DBSecurity->admins = new StdClass();
        $DBSecurity->admins->names = array($UserSuffix);
        $DBSecurity->members = new StdClass();
        $DBSecurity->members->names = array($UserSuffix);

		$response = \Httpful\Request::put($COUCHURL."/".$UserDB."/_security")
		                        ->sendsJson()
		                        ->body($DBSecurity)
		                        ->send();

        if (isset($response->body->ok) && $response->body->ok == 1)
			e("[!--] DB Granted.");
		else {
			print_r($response->body);
			die("[!] Cannot grant database");
		}

		$startKey = $WholeUser;
		e("[---] Rechecking.");

	}

}

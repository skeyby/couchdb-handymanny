<?php


$COUCHURL = "http://";
$COUCHUSER = "admin";
$COUCHPASSWORD = "";
$COUCHDATABASE = "";


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
e("CouchDB Align Cluster version 1.0");
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

e("[*] Tasting CouchDB nodes list");
$response = \Httpful\Request::get($COUCHURL."_membership")->send();

if (! isset($response->body->all_nodes) OR
	! isset($response->body->cluster_nodes) OR
	$response->body->all_nodes != $response->body->cluster_nodes) {
	e("[!] Error fetching node list: either no cluster configured, or not all the configured nodes are online. Please check the cluster setup before messing up the databases");
	die();
}
$CouchDB_Nodes = $response->body->all_nodes;
e("[*] Found the following nodes:");
foreach ($CouchDB_Nodes as $eachNode) {
	e("[-] ".$eachNode);
}
e("");


### Phase 3

e("[*] Fetching details about ".$COUCHDATABASE);
$response = \Httpful\Request::get($COUCHURL.$COUCHDATABASE)->send();

if (! isset($response->body->db_name) OR
	! isset($response->body->cluster)) {
	e("[!] Error fetching details about the DB");
	die();
}
$CouchDB_Database = $response->body;
e("[*] ".$COUCHDATABASE." size is ".$CouchDB_Database->sizes->file." bytes. It's currently divided in ".$CouchDB_Database->cluster->q." shards on ".$CouchDB_Database->cluster->n." nodes");
if (count($CouchDB_Nodes) != $CouchDB_Database->cluster->n) {
	e("[*] The cluster nodes count (".count($CouchDB_Nodes).") and the db nodes count (".$CouchDB_Database->cluster->n.") is currently different!");	
} else {
	e("[*] The cluster nodes count (".count($CouchDB_Nodes).") and the db nodes count (".$CouchDB_Database->cluster->n.") currently matches!");	
}

$response = \Httpful\Request::get($COUCHURL.$COUCHDATABASE."/_shards")->send();
if (! isset($response->body->shards)) {
	e("[!] Could not get the current shards layout!");
	die();
}

e("[*] Current shards layout:");
$foundNodes = array();
$foundShards = array();
foreach($response->body->shards as $eachShard => $shardNodes) {
	$foundShards[] = $eachShard;
	foreach ($shardNodes as $eachNode) {
		e("[-] Found Shard ".$eachShard." on ".$eachNode);		
		$foundNodes[] = $eachNode;
	}
}
$PopulatedNodes = array_unique($foundNodes);
$PopulatedShards = array_unique($foundShards);
e("[*] The DB is currently on the following nodes:");
foreach ($PopulatedNodes as $eachNode) {
	e("[-] ".$eachNode);
}

$EmptyNodes = array();
$EmptyNodes = array_diff($CouchDB_Nodes, $PopulatedNodes);
if (count($EmptyNodes) == 0) {
	e("[!] The DB seems to be on every node it should be!");
	die();
}

e("[*] The DB needs to be pushed on the following nodes:");
foreach ($EmptyNodes as $eachNode) {
	e("[-] ".$eachNode);
}
e("");


### Phase 4

e("[*] Fetching metadatas for ".$COUCHDATABASE);
$response = \Httpful\Request::get($COUCHURL."_node/_local/_dbs/".$COUCHDATABASE)->send();
if (! isset($response->body->changelog) OR
	! isset($response->body->by_node) OR
	! isset($response->body->by_range)) {
	e("[!] Problem parsing the metadata, bailing out!");
}
$Metadatas = clone $response->body;

foreach ($EmptyNodes as $eachNode) {
	foreach ($PopulatedShards as $eachShard) {
		e("[*] Adding Shard $eachShard and Node $eachNode to Changelog");
		$row = array();
		$row[] = "add";
		$row[] = $eachShard;
		$row[] = $eachNode;
		$Metadatas->changelog[] = $row;
	}
} 

foreach ($EmptyNodes as $eachNode) {
	e("[*] Populating ".$eachNode." on by_node");
	$Metadatas->by_node->$eachNode = $PopulatedShards;
}

foreach ($PopulatedShards as $eachShard) {
	foreach ($EmptyNodes as $eachNode) {
		e("[*] Adding Node $eachNode on Shard $eachShard to by_range");
		$Metadatas->by_range->$eachShard[] = $eachNode;
	}
}
e("");

### Phase 5

e("### Sleeping 10 seconds before updating CouchDB server... are you sure you want to continue? Please Control-C if you don't! ###");
e("");
sleep(10);

e("[*] OK! Let's go!");
e("");
e("[*] Updating the metadata definition!");
$response = \Httpful\Request::put($COUCHURL."_node/_local/_dbs/".$COUCHDATABASE)
                        ->sendsJson()
                        ->body($Metadatas)
                        ->send();

print_r($response->body);

e("[*] Asking for Shard Synchronization!");
$response = \Httpful\Request::post($COUCHURL.$COUCHDATABASE."/_sync_shards")
                        ->sendsJson()
                        ->send();

print_r($response->body);


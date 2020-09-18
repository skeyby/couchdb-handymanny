#!/usr/bin/php
<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/CouchDB-Connector.php';

if(!defined("STDIN")) {
    define("STDIN", fopen('php://stdin','rb'));
}

use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Colors;
use splitbrain\phpcli\Options;
use splitbrain\phpcli\TableFormatter;


class CouchDB_HandleDBs extends CLI
{
    private $count;

    // register options and arguments
    protected function setup(Options $options)
    {
        $options->setHelp('A very minimal example that does nothing but print a version');
        $options->registerOption('version', 'print version', 'v');
        $options->registerOption('url',      'couchdb url',      null, 'url');
        $options->registerOption('username', 'couchdb username', null, 'username');
        $options->registerOption('password', 'couchdb password', null, 'password');

        $options->registerCommand('ping-couchdb', 'Ping a CouchDB Instance');

        $options->registerCommand('get-tasks',    'Get currently running tasks');

        $options->registerCommand('details-cluster', 'Get details about Cluster Nodes');

        $options->registerCommand('list-dbs',     'Lists all the DBs in the server');
        $options->registerOption('start',         'Set start point for db list',  null, 'start',      'list-dbs');
        $options->registerOption('limit',         'Limits the amount of dbs reported',  null, 'limit', 'list-dbs');

        $options->registerCommand('sync-db',      'Resync the shards of a DB');
        $options->registerOption('database',      'Database to operate on',  null, 'database',      'sync-db');
        $options->registerOption('all-databases', 'Iterate on all databases on the server',  null, false, 'sync-db');

        $options->registerCommand('details-db',   'Get details of a DB');
        $options->registerOption('database',      'Database to operate on',  null, 'database',      'details-db');
        $options->registerOption('all-databases', 'Iterate on all databases on the server',  null, false, 'details-db');
        $options->registerOption('start-db',      'First db to iterate', null, 'start-db', 'details-db');

        $options->registerCommand('delete-db',    'Deletes a DB');
        $options->registerOption('database',      'Database to operate on',  null, 'database',      'delete-db');

        $options->registerCommand('create-db',    'Creates a DB');
        $options->registerOption('database',      'Database to operate on',  null, 'database',      'create-db');
        $options->registerOption('grant-admin',   'Database Admin Grants',   null, 'grant-admin',   'create-db');
        $options->registerOption('grant-members', 'Database Members Grants', null, 'grant-members', 'create-db');

        $options->registerCommand('grant-db',     'Add a grant on a DB');
        $options->registerOption('database',      'Database to operate on',  null, 'database',      'grant-db');
        $options->registerOption('grant-admin',   'Database Admin Grants',   null, 'grant-admin',   'grant-db');
        $options->registerOption('grant-members', 'Database Members Grants', null, 'grant-members', 'grant-db');

        $options->registerCommand('revoke-db',    'Revoke a grant from a DB');
        $options->registerOption('database',      'Database to operate on',  null, 'database',      'revoke-db');
        $options->registerOption('grant-admin',   'Database Admin Grants',   null, 'grant-admin',   'revoke-db');
        $options->registerOption('grant-members', 'Database Members Grants', null, 'grant-members', 'revoke-db');

        $options->registerCommand('rebalance-db', 'Rebalance a DB across a cluster');
        $options->registerOption('database',      'Database to operate on',  null, 'database',      'rebalance-db');
        $options->registerOption('all-databases', 'Iterate on all databases on the server',  null, false, 'rebalance-db');
        $options->registerOption('start-db',      'First db to iterate', null, 'start-db', 'rebalance-db');
        
        $options->registerCommand('migrate-db', 'Migrate a DB from a source to a destination set');
        $options->registerOption('database',      'Database to operate on',  null, 'database',      'migrate-db');
        $options->registerOption('source',      'Source node',  null, 'source',      'migrate-db');
        $options->registerOption('destination',      'Destination nodes separated by commas',  null, 'destination',      'migrate-db');
        $options->registerOption('all-databases', 'Iterate on all databases on the server',  null, false, 'migrate-db');
        $options->registerOption('start-db',      'First db to iterate', null, 'start-db', 'migrate-db');        
    }

    // implement your code
    protected function main(Options $options)
    {

        echo PHP_EOL;

        $url      = trim($options->getOpt('url'));
        $username = trim($options->getOpt('username'));
        $password = trim($options->getOpt('password'));

        if (!is_string($url) OR strlen($url) == 0 OR
            !is_string($username) OR strlen($username) == 0 OR
            !is_string($password) OR strlen($password) == 0
            ) {
                $this->error('No hostname, username or password specified. Use -h to show help');
                echo PHP_EOL;
        } else {
            switch ($options->getCmd()) {
                case 'ping-couchdb':
                    $this->pingHost($url, $username, $password);
                    break;
                case 'get-tasks':
                    $this->getTasks($url, $username, $password);
                    break;
                case 'details-cluster':
                    $this->detailsCluster($url, $username, $password);
                    break;
                case 'list-dbs':
                    if (trim($options->getOpt('start'))) {
                        $start = trim($options->getOpt('start'));
                    } else {
                        $start = "";
                    }                   
                    if (trim($options->getOpt('limit'))) {
                        $limit = trim($options->getOpt('limit'));
                    } else {
                        $limit = 25;
                    }
                    $this->listDBs($url, $username, $password, $start, $limit);
                    break;
                case 'sync-db':
                    $database = trim($options->getOpt('database'));
                    $allDatabases = $options->getOpt('all-databases');
                    if ($allDatabases !== true && (!is_string($database) OR strlen($database) == 0)) {
                        $this->error('No target database specified (--database) / no --all-databases specified');
                    } else {
                        $this->syncDB($url, $username, $password, $database);
                    }
                    break;
                case 'details-db':
                    $database = trim($options->getOpt('database'));
                    $allDatabases = $options->getOpt('all-databases');
                    if ($allDatabases !== true && (!is_string($database) OR strlen($database) == 0)) {
                        $this->error('No target database specified (--database) / no --all-databases specified');
                    } elseif ($allDatabases) {
                        $startDB = trim($options->getOpt('start-db'));
                        $this->pingHost($url, $username, $password);
                        
                        $this->loopAllDBs($url, $username, $password, function($database) use ($url, $username, $password) {
                            $this->fullDetailsDB($url, $username, $password, $database); 
                        }, $startDB);
                    } else {
                        $this->pingHost($url, $username, $password);
                        $this->fullDetailsDB($url, $username, $password, $database);
                    }
                    break;
                case 'create-db':
                    $database = trim($options->getOpt('database'));
                    if (!is_string($database) OR strlen($database) == 0) {
                        $this->error('No target database specified (--database)');
                    } else {
                        $this->createDB($url, $username, $password, $database, NULL, NULL);
                    }
                    break;
                case 'delete-db':
                    $database = trim($options->getOpt('database'));
                    if (!is_string($database) OR strlen($database) == 0) {
                        $this->error('No target database specified (--database)');
                    } else {
                        $this->deleteDB($url, $username, $password, $database);
                    }
                    break;
                case 'rebalance-db':
                    $database = trim($options->getOpt('database'));
                    $allDatabases = $options->getOpt('all-databases');
                    if ($allDatabases !== true && (!is_string($database) OR strlen($database) == 0)) {
                        $this->error('No target database specified (--database) / no --all-databases specified');
                    } elseif ($allDatabases) {
                        $startDB = trim($options->getOpt('start-db'));
                        $this->loopAllDBs($url, $username, $password, function($database) use ($url, $username, $password) {
                            $this->rebalanceDB($url, $username, $password, $database);
                        }, $startDB);
                    } else {
                        $this->rebalanceDB($url, $username, $password, $database);
                    }
                    break;
                case 'migrate-db':
                    $this->count = 0;
                    $database = trim($options->getOpt('database'));
                    $source = trim($options->getOpt('source'));
                    $destination = explode(",", trim($options->getOpt('destination')));
                    $allDatabases = $options->getOpt('all-databases');
                    if ($allDatabases !== true && (!is_string($database) OR strlen($database) == 0)) {
                        $this->error('No target database specified (--database) / no --all-databases specified');
                    } elseif ($allDatabases) {
                        $startDB = trim($options->getOpt('start-db'));
                        $this->loopAllDBs($url, $username, $password, function($database) use ($url, $username, $password, $source, $destination) {
                            $this->migrateDB($url, $username, $password, $database, $source, $destination);
                        }, $startDB);
                    } else {
                        $this->migrateDB($url, $username, $password, $database, $source, $destination);
                    }
                    break;                    
                default:
                    $this->error('No known command was called, let me show you the default help then:');
                    echo $options->help();
                    echo PHP_EOL;
                    exit;
            }
        }
        echo PHP_EOL;
    }
    
    private function loopAllDBs($url, $username, $password, $callback, $start = "") {
        $limit = 25;
        $CouchDB_C = new CouchDB_Connector($url, $username, $password);
        do {
            $this->info('Looping DBs - start '.$start);
            $allDBs = $CouchDB_C->getAllDBs($start, $limit);
            foreach ($allDBs as $database) {
                $callback($database);  
                $start = $database.".";
            }
        } while (count($allDBs) > 0);        
    }

    /** Function to Ping remote Database **/
    protected function pingHost($url, $username, $password) {

        $this->info('CouchDB Ping to '.$url);

        $CouchDB_C = new CouchDB_Connector($url, $username, $password);
        $ping = $CouchDB_C->pingHost();

        if ($ping === FALSE) {
            $this->error('Ping error');
            return false;
        } else {
            $this->success('Found CouchDB version '.$ping->version.' ('.$ping->uuid.')');
        }

        echo PHP_EOL;

    }


    protected function getTasks($url, $username, $password) {

        $this->pingHost($url, $username, $password);

        $CouchDB_C = new CouchDB_Connector($url, $username, $password);


        $this->info("Currently running tasks:");
        echo PHP_EOL;

        $allTasks = $CouchDB_C->getTasks();

        if (is_array($allTasks)) {
            $tf = new TableFormatter($this->colors);
            $tf->setMaxWidth(160);
            $tf->setBorder(' | '); // nice border between colmns

            echo $tf->format(
                array('20%', '20%', '*', '5%'),
                array('Type', 'Node', 'Database', '%')
            );

            echo str_pad('', $tf->getMaxWidth(), '-') . "\n";  

            foreach ($allTasks as $eachTask) {
                echo $tf->format(
                    array('20%', '20%', '*', '5%'),
                    array($eachTask->type, $eachTask->node, $eachTask->database, isset($eachTask->progress) ? $eachTask->progress."%" : '--'),
                    array(Colors::C_CYAN, Colors::C_GREEN, Colors::C_GREEN, Colors::C_GREEN)
                );
            }

        } else {
            if (is_string($status)) {
                $this->error($status);
                return false;
            } else {
                $this->error('Unknown error retrieving database informations');
                return false;
            }
        }

        echo PHP_EOL;
        $this->info("Currently running replicators:");
        echo PHP_EOL;

        $allReplications = $CouchDB_C->getReplicators();

        if (is_object($allReplications) && is_array($allReplications->jobs)) {

            $tf = new TableFormatter($this->colors);
            $tf->setMaxWidth(160);
            $tf->setBorder(' | '); // nice border between colmns

            echo $tf->format(
                array('5%', '15%', '10%', '*', '5%'),
                array('ID', 'Node', 'Database', 'Source', 'Status')
            );

            echo str_pad('', $tf->getMaxWidth(), '-') . "\n";  

            foreach ($allReplications->jobs as $eachReplication) {
                echo $tf->format(
                    array('5%', '15%', '10%', '*', '5%'),
                    array($eachReplication->doc_id, $eachReplication->node, $eachReplication->database, (isset($eachReplication->source) ? $eachReplication->source : '--')." => ".(isset($eachReplication->target) ? $eachReplication->target : '--'), $eachReplication->history[0]->type),
                    array(Colors::C_CYAN, Colors::C_GREEN, Colors::C_GREEN, Colors::C_GREEN, Colors::C_CYAN)
                );
            }
        }

        echo PHP_EOL;
        $this->info("Currently running reshards:");
        echo PHP_EOL;

        $allReshads = $CouchDB_C->getReshards();

        if (is_object($allReshads) && is_array($allReshads->jobs)) {

            $tf = new TableFormatter($this->colors);
            $tf->setMaxWidth(160);
            $tf->setBorder(' | '); // nice border between colmns

            echo $tf->format(
                array('20%', '20%', '*', '10%'),
                array('Type', 'Node', 'Shard', 'Status')
            );

            echo str_pad('', $tf->getMaxWidth(), '-') . "\n";  

            foreach ($allReshads->jobs as $eachJob) {
//                print_r($eachJob);
                echo $tf->format(
                    array('20%', '20%', '*', '10%'),
                    array($eachJob->type, $eachJob->node, $eachJob->source, isset($eachJob->job_state) ? $eachJob->job_state : '--'),
                    array(Colors::C_CYAN, Colors::C_GREEN, Colors::C_GREEN, Colors::C_GREEN)
                );
            }
        }

    }

    protected function listDBs($url, $username, $password, $start, $limit) {

        $this->pingHost($url, $username, $password);

        $CouchDB_C = new CouchDB_Connector($url, $username, $password);
        $allDBs = $CouchDB_C->getAllDBs($start, $limit);

        if (is_array($allDBs)) {
            $tf = new TableFormatter($this->colors);
            $tf->setBorder(' | '); // nice border between colmns

            echo $tf->format(
                array('10%', '*'),
                array('#', 'DB Name')
            );

            echo str_pad('', $tf->getMaxWidth(), '-') . "\n";  

            foreach ($allDBs as $Key => $Value) {
                echo $tf->format(
                    array('10%', '*'),
                    array($Key, $Value),
                    array(Colors::C_CYAN, Colors::C_GREEN)
                );
            }

        } else {
            if (is_string($status)) {
                $this->error($status);
                return false;
            } else {
                $this->error('Unknown error retrieving database informations');
                return false;
            }
        }

    }



    protected function fullDetailsDB($url, $username, $password, $database) {
        $this->detailDB($url, $username, $password, $database);
        $this->detailDBShards($url, $username, $password, $database);
        $this->detailDBPermissions($url, $username, $password, $database);
    }


    protected function detailDB($url, $username, $password, $database) {

        $this->info('Retrieving details about database '.$database.' on '.$url);

        $CouchDB_C = new CouchDB_Connector($url, $username, $password);
        $status = $CouchDB_C->getDB($database);

        if (is_object($status)) {

            echo PHP_EOL;
            $tf = new TableFormatter($this->colors);
            $tf->setBorder(' | '); // nice border between colmns

            echo $tf->format(
                array('40%', '*'),
                array('Property', 'Name')
            );

            echo str_pad('', $tf->getMaxWidth(), '-') . "\n";            

            $Table = array();
            $Table["Database Name"] = $status->db_name;
            $Table["Total Documents"] = $status->doc_count;
            $Table["Total Deleted Documents"] = $status->doc_del_count;
            $Table["Documents size"] = $status->sizes->active;
            $Table["Database size"] = $status->sizes->file;
            $Table["Cluster Replicas"] = $status->cluster->n;
            $Table["Cluster Shards"] = $status->cluster->q;
            $Table["Cluster Read Quorum"] = $status->cluster->r;
            $Table["Cluster Write Quorum"] = $status->cluster->w;
            $Table["Partitioned"] = (isset($status->props) && isset($status->props->partitioned) && $status->props->partitioned === true) ? "YES" : "NO";
            $Table["Compaction running"] = ($status->compact_running === true) ? "YES" : "NO";

            foreach ($Table as $Key => $Value) {
                echo $tf->format(
                    array('40%', '*'),
                    array($Key, $Value),
                    array(Colors::C_CYAN, Colors::C_GREEN)
                );
            }
        } else {
            if (is_string($status)) {
                $this->error($status);
                return false;
            } else {
                $this->error('Unknown error retrieving database informations');
                return false;
            }
        }

        echo PHP_EOL;

        return $status;

    }


    protected function detailDBShards($url, $username, $password, $database) {

        $this->info('Retrieving details about database '.$database.' shards on '.$url);

        $CouchDB_C = new CouchDB_Connector($url, $username, $password);
        $status = $CouchDB_C->getDBShards($database);

        if (is_object($status)) {

            echo PHP_EOL;
            $tf = new TableFormatter($this->colors);
            $tf->setBorder(' | '); // nice border between colmns

            echo $tf->format(
                array('50%', '50%'),
                array('Shard', 'Node')
            );

            echo str_pad('', $tf->getMaxWidth(), '-') . "\n";            

            foreach($status->shards as $eachShard => $shardNodes) {
                $foundShards[] = $eachShard;
                foreach ($shardNodes as $eachNode) {
                    echo $tf->format(
                        array('50%', '50%'),
                        array($eachShard, $eachNode),
                        array(Colors::C_CYAN, Colors::C_GREEN)
                    );
                }
            }

        } else {
            if (is_string($status)) {
                $this->error($status);
                return false;
            } else {
                $this->error('Unknown error retrieving database informations');
                return false;
            }
        }

        echo PHP_EOL;

        return $status;

    }


    protected function detailDBPermissions($url, $username, $password, $database) {

        $this->info('Retrieving permissions about database '.$database.' on '.$url);

        $CouchDB_C = new CouchDB_Connector($url, $username, $password);
        $status = $CouchDB_C->getDBPermissions($database);

        if (is_object($status)) {

            echo PHP_EOL;

            $tf = new TableFormatter($this->colors);
            $tf->setBorder(' | '); // nice border between colmns

            echo $tf->format(
                array('30%', '30%', '*'),
                array('Permission Level', 'Permission Type', 'Permission Target')
            );

            echo str_pad('', $tf->getMaxWidth(), '-') . "\n";            

            if (isset($status->admins)) {
                if (isset($status->admins->roles)) {
                    foreach ($status->admins->roles as $eachRole) {
                        echo $tf->format(
                            array('30%', '30%', '*'),
                            array('admin', 'group', $eachRole),
                            array(Colors::C_CYAN, Colors::C_GREEN, Colors::C_GREEN)
                        );
                    }
                }                
                if (isset($status->admins->names)) {
                    foreach ($status->admins->names as $eachName) {
                        echo $tf->format(
                            array('30%', '30%', '*'),
                            array('admin', 'user', $eachName),
                            array(Colors::C_CYAN, Colors::C_GREEN, Colors::C_GREEN)
                        );
                    }
                }                
            }
            if (isset($status->members)) {
                if (isset($status->members->roles)) {
                    foreach ($status->members->roles as $eachRole) {
                        echo $tf->format(
                            array('30%', '30%', '*'),
                            array('member', 'group', $eachRole),
                            array(Colors::C_CYAN, Colors::C_GREEN, Colors::C_GREEN)
                        );
                    }
                }                
                if (isset($status->members->names)) {
                    foreach ($status->members->names as $eachName) {
                        echo $tf->format(
                            array('30%', '30%', '*'),
                            array('member', 'user', $eachName),
                            array(Colors::C_CYAN, Colors::C_GREEN, Colors::C_GREEN)
                        );
                    }
                }                
            }

        } else {
            if (is_string($status)) {
                $this->error($status);
                return false;
            } else {
                $this->error('Unknown error retrieving database informations');
                return false;
            }
        }

        echo PHP_EOL;

        return $status;

    }

    /** Function to delete a database from a remote server **/
    protected function deleteDB($url, $username, $password, $database) {

        $this->pingHost($url, $username, $password);

        $this->info('Deleting database '.$database.' on '.$url);

        $this->detailDB($url, $username, $password, $database);

        $CouchDB_C = new CouchDB_Connector($url, $username, $password);

        $this->alert("Proceed with deletion? (yes/no): ");
        $answer = fread(STDIN, 80);
        if (trim(strtolower($answer)) == "yes") {

            $status = $CouchDB_C->deleteDB($database);

            if ($status === true) {
                $this->success('Database '.$database.' deleted on '.$url);
            } else {
                if (is_string($status)) {
                    $this->error($status);
                    return false;
                } else {
                    $this->error('Unknown error deleting database');
                    return false;
                }
            }
        } else {
            $this->error('Operation cancelled');
            return false;
        }

    }


    /** Function to create a database on a remote server **/
    protected function createDB($url, $username, $password, $database, $grantAdmin, $grantMembers) {

        $this->pingHost($url, $username, $password);

        $this->info('Creating database '.$database.' on '.$url);

        $CouchDB_C = new CouchDB_Connector($url, $username, $password);

        $status = $CouchDB_C->createDB($database);

        if ($status === true) {
            $this->success('Database '.$database.' created on '.$url);
        } else {
            if (is_string($status)) {
                $this->error($status);
                return false;
            } else {
                $this->error('Unknown error creating database');
                return false;
            }
        }

        $this->info('Appling grants to database '.$database.' on '.$url);

        $this->fullDetailsDB($url, $username, $password, $database);

    }

    protected function detailsCluster($url, $username, $password) {

        $this->pingHost($url, $username, $password);

        $this->info("Fetching nodes in the cluster:");

        $CouchDB_C = new CouchDB_Connector($url, $username, $password);
        $status = $CouchDB_C->getClusterNodes();

        if (is_array($status)) {
            foreach ($status as $eachNode) {
                $this->success(" Found node: ".$eachNode);
            }
        } else {
            $this->error('Error getting Cluster Nodes: maybe not all nodes are currently online?');
            return false;
        }

        echo PHP_EOL;        

        return $status;

    }

    protected function syncDB($url, $username, $password, $database) {

        $clusterDetails = $this->detailsCluster($url, $username, $password);
        if (! is_array($clusterDetails)) { return false; }

        $clusterNodes = count($clusterDetails);

        $dbDetails =  $this->detailDB($url, $username, $password, $database);
        if (! is_object($dbDetails)) { return false; }

        $this->detailDBShards($url, $username, $password, $database);

        $CouchDB_C = new CouchDB_Connector($url, $username, $password);
        $this->info("Resyncing shards for the database ".$database);
        $status = $CouchDB_C->syncDBShards($database);

        if ($status === true) {
            $this->success('Resync for '.$database.' queued');
        } else {
            if (is_string($status)) {
                $this->error($status);
                return false;
            } else {
                $this->error('Unknown error resyncing database');
                return false;
            }
        }

        echo PHP_EOL;
        return true;

    }
    
    private function audit(string $type, string $database, string $message) {
        file_put_contents("./audit.log","[".((new \DateTime())->format(\DateTime::ATOM))."] - TYPE: $type - DB: $database - ".$message.PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    protected function migrateDB(string $url, string $username, string $password, string $database, string $source, array $destinations) {
        $couchDBConnection = new CouchDB_Connector($url, $username, $password);
          
        // cluster n
        $systemConfig = $couchDBConnection->getConfig();

        if (!is_object($systemConfig) OR ! isset($systemConfig->cluster->n)) {
            $this->error("Could not get current Cluster settings");
            $this->audit("error", $database, "Could not get current Cluster settings");
            return false;
        }        
        
        // database shards
        $databaseShards = $this->detailDBShards($url, $username, $password, $database);
        
        if (!is_object($databaseShards)) {
            $this->error("Could not get DB shards");
            $this->audit("error", $database, "Could not get DB shards");
            return false;
        }  
        
        // get metadata e db permissions
        $Metadatas = $couchDBConnection->getDBMetadatas($database);

        if (!is_object($Metadatas)) {
            $this->error("Could not get database metadata");
            $this->audit("error", $database, "Could not get database metadata");
            return false;
        }        

        $dbPermissions = $this->detailDBPermissions($url, $username, $password, $database);   
        
        $saveIsNeeded = false;
        $nodesToCheck = [];
        
        // loop databse shards
        foreach($databaseShards->shards as $eachShard => $shardNodes) {
            // if this shard is in source
            if (in_array($source, $shardNodes)) {
                $this->info("Source has $eachShard shard");
                
                $saveIsNeeded = true;
                
                // whether we have more nodes with this shard then needed or not
                if (count($shardNodes) > $systemConfig->cluster->n) {
                    // delete shard on source
                    $this->warning("Removing Shard $eachShard and Node $source from Changelog");
                    $row = array();
                    $row[] = "remove";
                    $row[] = $eachShard;
                    $row[] = $source;
                    $Metadatas->changelog[] = $row;   
                    
                    $this->warning("Removing ".$source." from by_node");
                    if (isset($Metadatas->by_node->$source)) {
                        $Metadatas->by_node->$source = array_values(array_diff($Metadatas->by_node->$source, [$eachShard]));
                        if (count($Metadatas->by_node->$source) == 0) {
                            unset($Metadatas->by_node->$source);
                        }
                    }

                    $this->warning("Removing Node $source on Shard $eachShard from by_range");
                    if (isset($Metadatas->by_range->$eachShard)) {
                        $Metadatas->by_range->$eachShard = array_values(array_diff($Metadatas->by_range->$eachShard, [$source]));
                    }   
                } else {              
                    // add the shard on one of the destination nodes
                    $destinationsWithoutShard = array_diff($destinations, $shardNodes);
                    shuffle($destinationsWithoutShard);
                    $toAddNode = array_shift($destinationsWithoutShard);
            
                    $this->warning("Adding Shard $eachShard and Node $toAddNode to Changelog");
                    $row = array();
                    $row[] = "add";
                    $row[] = $eachShard;
                    $row[] = $toAddNode;
                    $Metadatas->changelog[] = $row;    
                    
                    $this->warning("Populating ".$toAddNode." on by_node");
                    if (!isset($Metadatas->by_node->$toAddNode)) {
                        $Metadatas->by_node->$toAddNode = [];
                    }
                    $Metadatas->by_node->$toAddNode[] = $eachShard;

                    $this->warning("Adding Node $toAddNode on Shard $eachShard to by_range");
                    if (!isset($Metadatas->by_range->$eachShard)) {
                        $Metadatas->by_range->$eachShard = [];
                    }                    
                    $Metadatas->by_range->$eachShard[] = $toAddNode;  
                    $nodesToCheck[] = $toAddNode;
                }     
            }
        }
        
        if ($saveIsNeeded) {
            $this->count++;
            $this->info("Saving updated metadatas for database ".$database);
            $status = $couchDBConnection->setDBMetadatas($database, $Metadatas);

            if ($status === true) {
                $this->success('Metadatas for '.$database.' updated ');
            } else {
                if (is_string($status)) {
                    $this->error($status);
                    $this->audit("error", $database, $status);
                    return false;
                } else {
                    $this->error('Unknown error updating metadatas');
                    $this->audit("error", $database, 'Unknown error updating metadatas');
                    return false;
                }
            }
            echo PHP_EOL;

            $this->info("Resyncing shards for the database ".$database);
            $status = $couchDBConnection->syncDBShards($database);

            if ($status === true) {
                $this->success('Resync for '.$database.' queued');
            } else {
                if (is_string($status)) {
                    $this->error($status);
                    $this->audit("error", $database, $status);
                    return false;
                } else {
                    $this->error('Unknown error resyncing database');
                    $this->audit("error", $database, 'Unknown error resyncing database');
                    return false;
                }
            }
            echo PHP_EOL;

            $this->info("Reappling permission to database ".$database);
            $status = $couchDBConnection->setDBPermissions($database, $dbPermissions);

            if ($status === true) {
                $this->success('Permission for '.$database.' updated');
            } else {
                if (is_string($status)) {
                    $this->error($status);
                    $this->audit("error", $database, $status);
                    return false;
                } else {
                    $this->error('Unknown error updating database permissions');
                    $this->audit("error", $database, 'Unknown error updating database permissions');
                    return false;
                }
            }
            
            if (count($nodesToCheck) > 0) {
                // sleep(3);
                $nodesToCheck = array_unique($nodesToCheck);
                foreach ($nodesToCheck as $nodeToCheck) {
                    $this->audit("info", $database, "Added shard on $nodeToCheck");
                    $this->info("Checking internal replication jobs for ".$nodeToCheck);

                    do {
                        $status = $couchDBConnection->getNodeSystem($nodeToCheck);
                        if (is_object($status)) {
                            $this->info("Internal replication jobs for ".$nodeToCheck.": ".$status->internal_replication_jobs);
                        }
                        if (!is_object($status) || $status->internal_replication_jobs !== 0) {
                            sleep(5);
                        } else {
                            break;
                        }
                    } while (true);
                }
            } else {
                $this->audit("info", $database, "Removed shard(s) from $source");
            }
            
            $this->info("Saved {$this->count} databases");
        } else {
            $this->info("Nothing to save");
        }

        echo PHP_EOL;            
               
    }

    protected function rebalanceDB($url, $username, $password, $database) {

        $CouchDB_C = new CouchDB_Connector($url, $username, $password);

        $clusterDetails = $this->detailsCluster($url, $username, $password);
        if (! is_array($clusterDetails)) { return false; }

        $clusterNodes = count($clusterDetails);

        $dbDetails =  $this->detailDB($url, $username, $password, $database);
        if (! is_object($dbDetails)) { return false; }

        $dbNodes = $dbDetails->cluster->n;

        $SystemConfig = $CouchDB_C->getConfig();

        if (!is_object($SystemConfig) OR ! isset($SystemConfig->cluster->n)) {
            $this->error("Could not get current Cluster settings");
            return false;
        }

        if ($clusterNodes == $dbNodes) {
            $this->success("Database is on ".$dbNodes." nodes of a ".$clusterNodes." cluster... everything looks fine!");
            return true;
        } else {
            if ($dbNodes >= $SystemConfig->cluster->n) {
                $this->success("Database is on ".$dbNodes." nodes of a ".$clusterNodes." nodes cluster... this respect the currently System 'cluster.n' value of ".$SystemConfig->cluster->n);
                return true;
            } else {
                $this->warning("Database is on ".$dbNodes." nodes of a ".$clusterNodes." nodes cluster... rebalance needed!");
                // number of nodes the database needs to be pushed
                $neededNodes = $SystemConfig->cluster->n - $dbNodes;
                $this->info("We need to put the DB on at least ".$neededNodes." nodes");
            }
            echo PHP_EOL;
        }
        
        // fetch current db shards details
        $databaseShards = $this->detailDBShards($url, $username, $password, $database);

        // find the nodes where the database currently exists
        $foundNodes = array();
        $foundShards = array();
        foreach($databaseShards->shards as $eachShard => $shardNodes) {
            $foundShards[] = $eachShard;
            foreach ($shardNodes as $eachNode) {
                $this->info("Found Shard ".$eachShard." on ".$eachNode);      
                $foundNodes[] = $eachNode;
            }
        }
        $PopulatedNodes = array_unique($foundNodes);
        $PopulatedShards = array_unique($foundShards);
        $this->info("The DB is currently on the following nodes:");
        foreach ($PopulatedNodes as $eachNode) {
            $this->info($eachNode);
        }

        // by difference, find the nodes where the database doesn't exists
        $EmptyNodes = array();
        $EmptyNodes = array_diff($clusterDetails, $PopulatedNodes);
        if (count($EmptyNodes) == 0) {
            $this->success("The DB seems to be on every node it should be!");
            return true;
        }

        $this->warning("The DB needs to be pushed on $neededNodes of the following nodes:");
        foreach ($EmptyNodes as $eachNode) {
            $this->warning($eachNode);
        }

        echo PHP_EOL;
        
        // find the nodes to add the database to
        if (count($EmptyNodes) == $neededNodes) {
            $ToAddNodes = $EmptyNodes;
        } else {
            shuffle($EmptyNodes);
            $ToAddNodes = array_slice($EmptyNodes, 0, $neededNodes);
        }

        $Metadatas = $CouchDB_C->getDBMetadatas($database);
        
        if (!is_object($Metadatas)) {
            $this->error("Could not get database metadata");
            return false;
        }        

        $dbPermissions = $this->detailDBPermissions($url, $username, $password, $database);

        foreach ($ToAddNodes as $eachNode) {
            foreach ($PopulatedShards as $eachShard) {
                $this->warning("Adding Shard $eachShard and Node $eachNode to Changelog");
                $row = array();
                $row[] = "add";
                $row[] = $eachShard;
                $row[] = $eachNode;
                $Metadatas->changelog[] = $row;
            }
        } 

        foreach ($ToAddNodes as $eachNode) {
            $this->warning("Populating ".$eachNode." on by_node");
            $Metadatas->by_node->$eachNode = $PopulatedShards;
        }

        foreach ($PopulatedShards as $eachShard) {
            foreach ($ToAddNodes as $eachNode) {
                $this->warning("Adding Node $eachNode on Shard $eachShard to by_range");
                $Metadatas->by_range->$eachShard[] = $eachNode;
            }
        }

        $this->info("Saving updated metadatas for database ".$database);
        $status = $CouchDB_C->setDBMetadatas($database, $Metadatas);

        if ($status === true) {
            $this->success('Metadatas for '.$database.' updated ');
        } else {
            if (is_string($status)) {
                $this->error($status);
                return false;
            } else {
                $this->error('Unknown error updating metadatas');
                return false;
            }
        }
        echo PHP_EOL;

        $this->info("Resyncing shards for the database ".$database);
        $status = $CouchDB_C->syncDBShards($database);

        if ($status === true) {
            $this->success('Resync for '.$database.' queued');
        } else {
            if (is_string($status)) {
                $this->error($status);
                return false;
            } else {
                $this->error('Unknown error resyncing database');
                return false;
            }
        }
        echo PHP_EOL;

        $this->info("Reappling permission to database ".$database);
        $status = $CouchDB_C->setDBPermissions($database, $dbPermissions);

        if ($status === true) {
            $this->success('Permission for '.$database.' updated');
        } else {
            if (is_string($status)) {
                $this->error($status);
                return false;
            } else {
                $this->error('Unknown error updating database permissions');
                return false;
            }
        }
        echo PHP_EOL;
        
        \sleep(2);

        echo $this->info("Re-executing to check current database situation:");

        $this->rebalanceDB($url, $username, $password, $database);

    }

}

// execute it
$cli = new CouchDB_HandleDBs();
$cli->run();

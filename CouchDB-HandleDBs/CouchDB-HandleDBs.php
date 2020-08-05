#!/usr/bin/php
<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/CouchDB-Connector.php';


use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Colors;
use splitbrain\phpcli\Options;
use splitbrain\phpcli\TableFormatter;


class CouchDB_HandleDBs extends CLI
{

    // register options and arguments
    protected function setup(Options $options)
    {
        $options->setHelp('A very minimal example that does nothing but print a version');
        $options->registerOption('version', 'print version', 'v');
        $options->registerOption('url',      'couchdb url',      null, 'url');
        $options->registerOption('username', 'couchdb username', null, 'username');
        $options->registerOption('password', 'couchdb password', null, 'password');

        $options->registerCommand('ping-couchdb', 'Ping a CouchDB Instance');

        $options->registerCommand('details-db',   'Get details of a DB');
        $options->registerOption('database',      'Database to operate on',  null, 'database',      'details-db');

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
                    break;
                case 'details-db':
                    $database = trim($options->getOpt('database'));
                    if (!is_string($database) OR strlen($database) == 0) {
                        $this->error('No target database specified (--database)');
                    } else {
                        $this->pingDB($url, $username, $password);
                        $this->detailDB($url, $username, $password, $database);
                        $this->detailDBShards($url, $username, $password, $database);
                        $this->detailDBPermissions($url, $username, $password, $database);
                    }
                    break;
                case 'create-db':
                    $this->success('The foo command was called');
                    break;
                case 'delete-db':
                    $database = trim($options->getOpt('database'));
                    if (!is_string($database) OR strlen($database) == 0) {
                        $this->error('No target database specified (--database)');
                    } else {
                        $this->deleteDB($url, $username, $password, $database);
                    }
                    break;
                default:
                    $this->error('No known command was called, we show the default help instead:');
                    echo $options->help();
                    echo PHP_EOL;
                    exit;
            }
        }
        echo PHP_EOL;
    }


    /** Function to Ping remote Database **/
    protected function pingDB($url, $username, $password) {

        $this->info('CouchDB Ping to '.$url);

        $CouchDB_C = new CouchDB_Connector($url, $username, $password);
        $ping = $CouchDB_C->pingDB();

        if ($ping === FALSE) {
            $this->error('Ping error');
        } else {
            $this->success('Found CouchDB version '.$ping->version.' ('.$ping->uuid.')');
        }

        echo PHP_EOL;

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
                array('50%', '*'),
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
                    array('50%', '*'),
                    array($Key, $Value),
                    array(Colors::C_CYAN, Colors::C_GREEN)
                );
            }

        } else {
            if (is_string($status)) {
                $this->error($status);
            } else {
                $this->error('Unknown error retrieving database informations');
            }
        }

        echo PHP_EOL;

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
            } else {
                $this->error('Unknown error retrieving database informations');
            }
        }

        echo PHP_EOL;

    }


    protected function detailDBPermissions($url, $username, $password, $database) {

        $this->info('Retrieving details about database '.$database.' shards on '.$url);

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
            } else {
                $this->error('Unknown error retrieving database informations');
            }
        }

        echo PHP_EOL;

    }

    /** Function to delete a database from a remote server **/
    protected function deleteDB($url, $username, $password, $database) {

        $this->pingDB($url, $username, $password);

        $this->info('Deleting database '.$database.' on '.$url);

        $CouchDB_C = new CouchDB_Connector($url, $username, $password);
        $status = $CouchDB_C->deleteDB($database);

        if ($status === true) {
            $this->success('Database '.$database.' deleted on '.$url);
        } else {
            if (is_string($status)) {
                $this->error($status);
            } else {
                $this->error('Unknown error deleting database');
            }
        }

    }


}

// execute it
$cli = new CouchDB_HandleDBs();
$cli->run();

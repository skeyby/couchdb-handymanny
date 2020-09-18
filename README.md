# couchdb-handymanny
A general purpose CouchDB set of command line utilities

CouchDB comes with some interesting WEB Gui to do all the management (Futon, Fauxton, Photon, ...) but sometimes you may need to interact with CouchDB from the command line, either because you may be working in an SSH session, or maybe just because you need to automate some kind of interaction.

CouchDB Handy-Manny purpose is just this: helping the system administrator to quickly interact with a CouchDB Instance (Stand-Alone or Clustered).

## Features list

CouchDB Handy-Manny currently supports the following operations:

- Check for CouchDB status (ping)
- Get currently running tasks (get-tasks)
- Get details of the cluster (detail-cluster)
- Get a list of all the dbs in the cluster (list-dbs)
- Create a database (create-db)
- Get the details about a database in the cluster (detail-db)
- Drop a database (delete-db)
- Add a grant to a db (grant-db)
- Revoke a grant from a db (revoke-db)
- Resync the shard of a DB in the cluster (sync-db)
- Rebalance a database on all the nodes in the cluster - if needed (rebalance-db)
- Migrate a database off a node to other nodes - respecting the required cluster redundancy settings (migrate-db)

In general, all the commands are in the syntax <action>-<target>.

## System requirements

## Installation instructions

## Usage details

All the commands in CouchDB Handy-Manny require some parameters. Some parameters are general and, thus, can be applied to all the command, while some parameters are command specific.

This is a list of the _common_ parameters:

- --username <username> : allows you to specify the username to use to connect to CouchDB. Altough not technically _required_, quite certainly you want this user to be an _admin or some functionalities may not work correclty
- --password <password> : the password for the username you just specified
- --url <url> : the url for the CouchDB istance you want to connect to. It could be http://127.1:5984/ or https://mycluster.couch/ or http://node-01.couch/ or whatever makes sense to you (and to _curl_)
- --loglevel <level> : filter out CouchDB HandyManny chattiness...  Valid levels are: debug, info, notice, success, warning, error, critical, alert, emergency.

### ping-couchdb

This command tells you if the --url you specified currently hosts a running CouchDB Instance, that can be accessed with given --username and --password.

This command doesn't require any additional parameter.

Example:

```
# ./CouchDB-HandleDBs.php --url http://127.0.0.1:5984/ --username admin --password whatever ping-couchdb

ℹ CouchDB Ping to http://127.0.0.1:5984/
✓ Found CouchDB version 3.1.0 (fca1fcf73ceab1d158777fc2ade7a583)
```


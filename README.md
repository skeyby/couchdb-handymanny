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

In general, all the commands are in the syntax _\<action\>-\<target\>_.

## System requirements

## Installation instructions

## Usage details

All the commands in CouchDB Handy-Manny require some parameters. Some parameters are general and, thus, can be applied to all the command, while some parameters are command specific.

This is a list of the _common_ parameters:

- --username \<username\> : allows you to specify the username to use to connect to CouchDB. Altough not technically _required_, quite certainly you want this user to be an _admin or some functionalities may not work correclty
- --password \<password\> : the password for the username you just specified
- --url \<url\> : the url for the CouchDB istance you want to connect to. It could be http://127.1:5984/ or https://mycluster.couch/ or http://node-01.couch/ or whatever makes sense to you (and to _curl_)
- --loglevel \<level\> : filters out CouchDB Handy-Manny chattiness...  Valid levels are: debug, info, notice, success, warning, error, critical, alert, emergency.

### ping-couchdb

This command tells you if the --url you specified currently hosts a running CouchDB Instance, that can be accessed with given --username and --password.

This command doesn't require any additional parameter.

The output reports the status of the ping command, the reported version of the CouchDB instance and the UUID for the specific instance.

Example:

```
# ./CouchDB-HandleDBs.php --url http://127.0.0.1:5984/ --username admin --password whatever ping-couchdb

ℹ CouchDB Ping to http://127.0.0.1:5984/
✓ Found CouchDB version 3.1.0 (fca1fcf73ceab1d158777fc2ade7a583)
```

### get-tasks

This command fetches all currently running task on CouchDB's cluster. Keep in mind that even if asking to a single node, you'll get everything running on every node.

This command doesn't require any additional parameter.

The output is divided in three sections:
- General currently running tasks: contains any task running in the cluster, like replication, compactions, indexing and whatever - as reported by the /\_active_tasks endpoint
- Currently running replicators: contains the details for the active replicators processes - as reported by the /\_scheduler/jobs endpoint 
- Currently resharding tasks: contains the details for the active reshard jobs - as reported by the /\_reshard/jobs endpoint

Example:

```
# ./CouchDB-HandleDBs.php --url http://node-04/backend/ --username admin --password whatever get-tasks

ℹ CouchDB Ping to http://node-04/backend/
✓ Found CouchDB version 3.1.0 (e6d3ff96d7ec59b3185e3d27a0036c0f)

ℹ Currently running tasks:

Type                           | Node                           | Database                                                                             | %
----------------------------------------------------------------------------------------------------------------------------------------------------------------
replication                    | node-05@10.133.xxx.xx          | shards/80000000-9fffffff/_replicator.1592990745                                      | --

ℹ Currently running replicators:

ID      | Node                   | Database       | Source                                                                                             | Status
----------------------------------------------------------------------------------------------------------------------------------------------------------------
IO2Home | node-05@10.133.xxx.xx  | _replicator    | http://node-replication:*****@10.133.xxx.xx:5984/_users/ => http://127.0.0.1:5984/_users/         | started

ℹ Currently running reshards:

Type                           | Node                           | Shard                                                                        | Status
----------------------------------------------------------------------------------------------------------------------------------------------------------------
```

### detail-cluster

This command reports the current layout for the Cluster.

This command doesn't require any additional parameter.

The output reports currently know nodes in the cluster.

Example:
```
# ./CouchDB-HandleDBs.php --url http://node-04/backend/ --username admin --password whatever detail-cluster

ℹ CouchDB Ping to http://node-04/backend/
✓ Found CouchDB version 3.1.0 (e6d3ff96d7ec59b3185e3d27a0036c0f)

ℹ Fetching nodes in the cluster:
✓  Found node: couchdb@10.133.xxx.xxx
✓  Found node: couchdb@10.133.xxx.xx
✓  Found node: couchdb@10.133.xxx.xx
✓  Found node: couchdb@10.133.xx.xx
✓  Found node: node-05@10.133.xxx.xx
✓  Found node: node-06@10.133.xxx.xx
```

### list-dbs

This command reports the databases in the CouchDB Cluster/Instance.

This command supports two optional additional parameters:

- --start-db \<start\> : start the listing from this DB. If omitted the list starts from the beginning
- --limit \<limit\> : limit the list to this number of DBs. If omitted the default is 25 DBs.

The output reports the DBs found on CouchDB.

Example 1:
```
# ./CouchDB-HandleDBs.php --url http://node-04/backend/ --username admin --password whatever list-dbs

ℹ CouchDB Ping to http://node-04/backend/
✓ Found CouchDB version 3.1.0 (e6d3ff96d7ec59b3185e3d27a0036c0f)

#       | DB Name
-------------------------------------------------------------------------------
0       | _replicator
1       | _users
2       | documents
3       | queue
4       | smarmella
5       | userdb-4241414c435537344332304638383056
6       | userdb-4241414d525a30334d30334439373248
7       | userdb-4241415344523037543033483530314f
8       | userdb-424149434d4c38314835364130323450
9       | userdb-424149434d4c38394c3633463833394e
10      | userdb-424149434d4e3934533132463833394e
11      | userdb-42414943534d38315232374639313255
12      | userdb-42414943535436375336334233303045
13      | userdb-424149444e4c3536543137453836344d
14      | userdb-424149474c493032443531443936395a
15      | userdb-424149474c4930364d34344835303152
16      | userdb-424149474c4e38354d35374237313549
17      | userdb-424149474e4e36374130394c3638324c
18      | userdb-42414947505037325331364330303255
19      | userdb-42414947505037374d36324130363449
20      | userdb-4241494750503938483237423936334e
21      | userdb-42414947524c3038443136493735344c
22      | userdb-4241494b564e39385230344c36383248
23      | userdb-4241494c47553438503134463833394e
24      | userdb-4241494c534530394c35364132393052
```

Example 2:

```
# ./CouchDB-HandleDBs.php --url http://node-04/backend/ --username admin --password whatever list-dbs --limit 3

ℹ CouchDB Ping to http://node-04/backend/
✓ Found CouchDB version 3.1.0 (e6d3ff96d7ec59b3185e3d27a0036c0f)

#       | DB Name
-------------------------------------------------------------------------------
0       | _replicator
1       | _users
2       | documents
```

### create-db

This command creates a DB in the CouchDB Cluster/Instance

This command supports the following additional parameters:
- --database \<database\> : the name of the database to create. This parameter is, surprisingly, mandatory.
- --grant-admin \<grant\> : the admins grant to apply to the DB. Not implemented yet.
- --grant-members \<grant\> : the members grant to apply to the DB. Not implemented yet.

The output reports the outcome for the operation and all the details for the newly-created database, including it's size, it's N, R, W values, shards placement and permissions.

Example:
```
# ./CouchDB-HandleDBs.php --url --url http://node-04/backend/ --username admin --password whatever create-db --database astronomy

ℹ CouchDB Ping to http://node-04/backend/
✓ Found CouchDB version 3.1.0 (e6d3ff96d7ec59b3185e3d27a0036c0f)

ℹ Creating database astronomy on http://node-04/backend/
✓ Database astronomy created on http://node-04/backend/
ℹ Appling grants to database astronomy on http://node-04/backend/
ℹ Retrieving details about database astronomy on http://node-04/backend/

Property                       | Name
-------------------------------------------------------------------------------
Database Name                  | astronomy
Total Documents                | 0
Total Deleted Documents        | 0
Documents size                 | 0
Database size                  | 16709
Cluster Replicas               | 3
Cluster Shards                 | 2
Cluster Read Quorum            | 2
Cluster Write Quorum           | 2
Partitioned                    | NO
Compaction running             | NO

ℹ Retrieving details about database astronomy shards on http://node-04/backend/

Shard                                  | Node
-------------------------------------------------------------------------------
00000000-7fffffff                      | couchdb@10.133.xxx.xxx
00000000-7fffffff                      | couchdb@10.133.xxx.xx
00000000-7fffffff                      | home-06@10.133.xxx.xx
80000000-ffffffff                      | couchdb@10.133.xxx.xx
80000000-ffffffff                      | couchdb@10.133.xx.xx
80000000-ffffffff                      | home-05@10.133.xxx.xx

ℹ Retrieving permissions about database astronomy on http://node-04/backend/

Permission Level      | Permission Type       | Permission Target
-------------------------------------------------------------------------------
admin                 | group                 | _admin
member                | group                 | _admin
```


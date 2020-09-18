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

- --username \<username\> : allows you to specify the username to use to connect to CouchDB. Altough not technically _required_, quite certainly you want this user to be an \_admin or some functionalities may not work correclty
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
00000000-7fffffff                      | node-06@10.133.xxx.xx
80000000-ffffffff                      | couchdb@10.133.xxx.xx
80000000-ffffffff                      | couchdb@10.133.xx.xx
80000000-ffffffff                      | node-05@10.133.xxx.xx

ℹ Retrieving permissions about database astronomy on http://node-04/backend/

Permission Level      | Permission Type       | Permission Target
-------------------------------------------------------------------------------
admin                 | group                 | _admin
member                | group                 | _admin
```

### detail-db

This command reports all the details about a specified database.

This command supports some additional parameters:
- --database \<database\> : specify the DB you want to know the details of.
- --all-databases : tells CouchDB Handy-Manny to iterate on all the DBs in the cluster outputting the details for all of them
- --start-db \<start-db\> : when using --all-databases allows you to specify the starting point for the iterator.

Quite obviously --database and --all-database are mutually exclusive.

The output will details all the information that can be obtained about the DB, divided in a few sections:

#### generic informations

- Database name: well...
- Total documents: the current total number of documents in the DB, **excluding the deleted ones**.
- Total delete documents: the current total number of deleted documents in the DB.
- Documents size: the sum of the size of each document in the DB, so basically that's the size of your datas.
- Database size: the size of the database on disk, so basically that's the disk usage of your datas.
- Cluster replicas: that's the "N" value for the database. It basically indicates how many copies of each documents will be placed in the cluster.
- Cluster shards: that's the "Q" value for the database. It basically indicates how many slices, or shards are used to divide the datas in the cluster.
- Cluster read quorum: that's the "R" value for the database. It basically indicates how many nodes in the cluster must read the data that is being requested in order for the cluster to give it back to the client.
- Cluster write quorumt: that's the "W" value for the database. It basically indicate how many nodes in the cluster must have succesfully written the data on disk before the cluster reports the write operation as successfull to the client.
- Partitioned: tells you if the database is partitioned or not
- Compaction running: tells you if the internal compaction daemon is currently working on this database

_Note: for a more detailed explanation of the N/Q/R/W values please refer to official CouchDB documentation._

#### shards informations

This table contains the details of every shard for the database and it's placement among the nodes in the cluster. You'll have "N" x "Q" shards placed on random nodes in the Cluster.

#### permissions informations

This table reports the permissions applied to a database. Currently CouchDB supports a permission level (that can be _admin_ or _member_), to different permission types (that can be _group_ or _user_) and any permission target you want (that is a group name, an username). The applied permission is the result of the combination of the rows in this table.

Example:
```
./CouchDB-HandleDBs.php --url http://node-04/backend/ --username admin --password whatever details-db --database astronomy

ℹ CouchDB Ping to http://node-04/backend/
✓ Found CouchDB version 3.1.0 (e6d3ff96d7ec59b3185e3d27a0036c0f)

ℹ Retrieving details about database astronomy on http://node-04/backend/

Property                       | Name
-------------------------------------------------------------------------------
Database Name                  | astronomy
Total Documents                | 0
Total Deleted Documents        | 0
Documents size                 | 0
Database size                  | 16708
Cluster Replicas               | 3
Cluster Shards                 | 2
Cluster Read Quorum            | 2
Cluster Write Quorum           | 2
Partitioned                    | NO
Compaction running             | NO

ℹ Retrieving details about database astronomy shards on http://node-04/backend/

Shard                                  | Node
-------------------------------------------------------------------------------
00000000-7fffffff                      | couchdb@10.133.109.142
00000000-7fffffff                      | couchdb@10.133.136.55
00000000-7fffffff                      | node-06@10.133.138.29
80000000-ffffffff                      | couchdb@10.133.138.27
80000000-ffffffff                      | couchdb@10.133.98.18
80000000-ffffffff                      | node-05@10.133.138.28

ℹ Retrieving permissions about database astronomy on http://node-04/backend/

Permission Level      | Permission Type       | Permission Target
-------------------------------------------------------------------------------
admin                 | group                 | _admin
member                | group                 | _admin
```

### delete-db

This command deletes a DB on current CouchDB Cluster/Instance.

This command supports the following additional parameter:
- --database \<database\> : the name of the database to delete. This parameter is, surprisingly, mandatory.

When run, the command will output a short brief detail of the DB to allow you to be sure of what you're doing, and will require you to answer either yes or no fo the deletion.

Example:
```
# ./CouchDB-HandleDBs.php --url http://node-04/backend/ --username admin --password whatever delete-db --database astronomy

ℹ CouchDB Ping to http://node-04/backend/
✓ Found CouchDB version 3.1.0 (e6d3ff96d7ec59b3185e3d27a0036c0f)

ℹ Deleting database astronomy on http://node-04/backend/
ℹ Retrieving details about database astronomy on http://node-04/backend/

Property                       | Name
-------------------------------------------------------------------------------
Database Name                  | astronomy
Total Documents                | 0
Total Deleted Documents        | 0
Documents size                 | 0
Database size                  | 16708
Cluster Replicas               | 3
Cluster Shards                 | 2
Cluster Read Quorum            | 2
Cluster Write Quorum           | 2
Partitioned                    | NO
Compaction running             | NO

✖ Proceed with deletion? (yes/no):
yes
✓ Database astronomy deleted on http://node-04/backend/
```

### grant-db

This command will allow you to add a grant to a database, when it will be ready.

### revoke-db

This command will allow you to revoke a grant from a database, when it will be ready.

### sync-db

This command allows you to trigger a shard resynchronization among the cluster: in general when you alter the Cluster shards, CouchDB automatically resync the shards of the database at the first new write in the DB. Fact is you may have some frequently-read and hardly-written DBs and could prefer to ask for an immediate resync on the shards. Here you are.

This command supports some additional parameters:
- --database \<database\> : specify the DB you want to resync
- --all-databases : tells CouchDB Handy-Manny to iterate on all the DBs in the cluster resyning all of them.

This command should also include a --start-db parameter, yet to be done.

The output for the command is the detail for the DB (so you can check the shards or whatever) and a report that the resync request has been posted to CouchDB:

Example:
```
# ./CouchDB-HandleDBs.php --url http://node-04/backend/ --username admin --password whatever sync-db --database queue

ℹ CouchDB Ping to http://node-04/backend/
✓ Found CouchDB version 3.1.0 (e6d3ff96d7ec59b3185e3d27a0036c0f)

ℹ Fetching nodes in the cluster:
✓  Found node: couchdb@10.133.xxx.xxx
✓  Found node: couchdb@10.133.xxx.xx
✓  Found node: couchdb@10.133.xx.xx
✓  Found node: node-05@10.133.xxx.xx
✓  Found node: node-06@10.133.xxx.xx

ℹ Retrieving details about database queue on http://node-04/backend/

Property                       | Name
-------------------------------------------------------------------------------
Database Name                  | queue
Total Documents                | 11247
Total Deleted Documents        | 493934
Documents size                 | 135024903
Database size                  | 178311630
Cluster Replicas               | 3
Cluster Shards                 | 2
Cluster Read Quorum            | 2
Cluster Write Quorum           | 2
Partitioned                    | NO
Compaction running             | NO

ℹ Retrieving details about database queue shards on http://node-04/backend/

Shard                                  | Node
-------------------------------------------------------------------------------
00000000-7fffffff                      | couchdb@10.133.xxx.xx
00000000-7fffffff                      | node-05@10.133.xxx.xx
00000000-7fffffff                      | node-06@10.133.xxx.xx
80000000-ffffffff                      | couchdb@10.133.xxx.xx
80000000-ffffffff                      | node-05@10.133.xxx.xx
80000000-ffffffff                      | node-06@10.133.xxx.xx

ℹ Resyncing shards for the database queue
✓ Resync for queue queued
```

### rebalance-db

This command will check for the Cluster "N" value against a Database "N" value. The Database "N" number is set on database creation in order to match the Cluster "N" value, what happens is that if you add new nodes later on to the Cluster, the Cluster "N" value gets higher but the "N" value for already existing databases is not updated. This will lead to "uneven" situation when you add nodes but actually only the old one will keep the datas and basically get all the work done. As said, this command will the Cluster "N" and Database "N" and, if Database "N" is lower than Cluster "N", it will teach CouchDB to copy some shards of the DB to a few other nodes in order to pair them up. After updating the metadatas for the database (read fixing the N value) it will also call the shard resync for you, what a nice guy. It Cluster "N" and Database "N" match or Database "N" is higher than Cluster "N" it will silently pass on.

This command supports some additional parameters:
- --database \<database\> : specify the DB you want to rebalance
- --all-databases : tells CouchDB Handy-Manny to iterate on all the DBs in the cluster rebalancing all of them
- --start-db \<start-db\> : when using --all-databases allows you to specify the starting point for the iterator

The output for this command includes the details for the DB you want to operate on, and a details wether the DB needs a rebalancing or not. If the DB needs a rebalancing, it will report which nodes in the cluster don't have an active copy of the datas, and then report which Nodes will host new copies of the datas (CouchDB Handy-Manny will pick out enough Random Nodes in order to match up the Database "N" to the Cluster "N"). After completing the operation CouchDB Handy-Manny will print out the database details again so that you can check what has happened.

Example:
```
# ./CouchDB-HandleDBs.php --url http://node-04/backend/ --username admin --password whatever rebalance-db --database userdb-43445652535237344134344638333952

ℹ CouchDB Ping to http://node-04/backend/
✓ Found CouchDB version 3.1.0 (e6d3ff96d7ec59b3185e3d27a0036c0f)

ℹ Fetching nodes in the cluster:
✓  Found node: couchdb@10.133.xxx.xxx
✓  Found node: couchdb@10.133.xxx.xx
✓  Found node: couchdb@10.133.xxx.xx
✓  Found node: couchdb@10.133.xx.xx
✓  Found node: node-05@10.133.xxx.xx
✓  Found node: node-06@10.133.xxx.xx

ℹ Retrieving details about database userdb-43445652535237344134344638333952 on http://node-04/backend/

Property                       | Name
-------------------------------------------------------------------------------
Database Name                  | userdb-43445652535237344134344638333952
Total Documents                | 1
Total Deleted Documents        | 0
Documents size                 | 3596
Database size                  | 328074
Cluster Replicas               | 2
Cluster Shards                 | 2
Cluster Read Quorum            | 2
Cluster Write Quorum           | 2
Partitioned                    | NO
Compaction running             | NO

⚠ Database is on 2 nodes of a 6 nodes cluster... rebalance needed!
ℹ We need to put the DB on at least 1 nodes

ℹ Retrieving details about database userdb-43445652535237344134344638333952 shards on http://node-04/backend/

Shard                                  | Node
-------------------------------------------------------------------------------
00000000-7fffffff                      | couchdb@10.133.xxx.xxx
00000000-7fffffff                      | couchdb@10.133.xx.xx
80000000-ffffffff                      | couchdb@10.133.xxx.xxx
80000000-ffffffff                      | couchdb@10.133.xx.xx

ℹ Found Shard 00000000-7fffffff on couchdb@10.133.xxx.xxx
ℹ Found Shard 00000000-7fffffff on couchdb@10.133.xx.xx
ℹ Found Shard 80000000-ffffffff on couchdb@10.133.xxx.xxx
ℹ Found Shard 80000000-ffffffff on couchdb@10.133.xx.xx
ℹ The DB is currently on the following nodes:
ℹ couchdb@10.133.xxx.xxx
ℹ couchdb@10.133.xx.xx
⚠ The DB needs to be pushed on 1 of the following nodes:
⚠ couchdb@10.133.xxx.xx
⚠ couchdb@10.133.xxx.xx
⚠ node-05@10.133.xxx.xx
⚠ node-06@10.133.xxx.xx

ℹ Retrieving permissions about database userdb-43445652535237344134344638333952 on http://node-04/backend/

Permission Level      | Permission Type       | Permission Target
-------------------------------------------------------------------------------
admin                 | user                  | CDVRSR74A44F839R
member                | user                  | CDVRSR74A44F839R

⚠ Adding Shard 00000000-7fffffff and Node couchdb@10.133.xxx.xx to Changelog
⚠ Adding Shard 80000000-ffffffff and Node couchdb@10.133.xxx.xx to Changelog
⚠ Populating couchdb@10.133.xxx.xx on by_node
⚠ Adding Node couchdb@10.133.xxx.xx on Shard 00000000-7fffffff to by_range
⚠ Adding Node couchdb@10.133.xxx.xx on Shard 80000000-ffffffff to by_range
ℹ Saving updated metadatas for database userdb-43445652535237344134344638333952
✓ Metadatas for userdb-43445652535237344134344638333952 updated

ℹ Resyncing shards for the database userdb-43445652535237344134344638333952
✓ Resync for userdb-43445652535237344134344638333952 queued

ℹ Reappling permission to database userdb-43445652535237344134344638333952
✓ Permission for userdb-43445652535237344134344638333952 updated

ℹ Re-executing to check current database situation:
ℹ CouchDB Ping to http://node-04/backend/
✓ Found CouchDB version 3.1.0 (e6d3ff96d7ec59b3185e3d27a0036c0f)

ℹ Fetching nodes in the cluster:
✓  Found node: couchdb@10.133.xxx.xxx
✓  Found node: couchdb@10.133.xxx.xx
✓  Found node: couchdb@10.133.xxx.xx
✓  Found node: couchdb@10.133.xx.xx
✓  Found node: node-05@10.133.xxx.xx
✓  Found node: node-06@10.133.xxx.xx

ℹ Retrieving details about database userdb-43445652535237344134344638333952 on http://node-04/backend/

Property                       | Name
-------------------------------------------------------------------------------
Database Name                  | userdb-43445652535237344134344638333952
Total Documents                | 1
Total Deleted Documents        | 0
Documents size                 | 3456
Database size                  | 45452
Cluster Replicas               | 3
Cluster Shards                 | 2
Cluster Read Quorum            | 2
Cluster Write Quorum           | 2
Partitioned                    | NO
Compaction running             | NO

✓ Database is on 3 nodes of a 6 nodes cluster... this respect the currently System 'cluster.n' value of 3
```

### migrate-db

This one of the most powerful command of CouchDB Handy-Manny. It's purpose it to allow you to migrate a DB (or all the DBs) off a node to other nodes in the Cluster, for example because you want to gracefully take a node out of the cluster and need to be sure that there are no active DBs on it.
The command has a "two steps" sequence:
- On the first run it will check current Shards layout for the DB under investigation and check if the DB has any shard on the "source" node. It if has no shards there, it will silently pass over.
- If the DB has any active shard on the "source" node, Handy-Manny will force replication of the shards that are on the "source" node among one of the desiredr "destinations". You'll need more than one possible destination because the destination may already have a copy of that shard. Handy-Manny will sort out this for you. During this phase the Cluster Replica for the specific DB will get artificially higher by one unit.
- Handy-Manny will now exit allowing you to choose when to go for the second step.
- When invoked the second time, Handy-Manny will check that, by removing the "source" node, the Database "N" would still match the Cluster "N". Considering that we artifically got it higher in the previous step, if everything is fine and aligned, Handy-Manny will remove the shards from the "source" node.

Please consider a few things:
- Removing a shard doesn't delete the data files from the "source" database. This is either good news, if anything should go wrong, and also bad news, if you expect for example for disk space to free up.
- CouchDB Handy-Manny will try to monitor the internal replication for the shards when performing the first step, yet, before performing the second one on very big databases with low "N", I'd suggest you to check that the destination nodes are effectively aligned. Just to be sure.
- When performing a migration of "--all-databases" Handy-Manny will mix phase one and phase two according to the situation of each database it's iterating on: if on the first run it find a DB that already has an higher N (for example because you did something strange in the past) it may directly perform step two.
- When working in migrate-db mode, Handy-Manny will write an audit.log where it reports everything it's doing. That's especially useful when working in --all-databases mode.
- Before migrating a whole node, I suggest you to do a --all-database rebalance-db, yet again, just to be sure.

This command suppots the following additional parameters:
- --database \<database\> : the database to migrate
- --source \<source\> : the source node for the migration
- --destination \<destination1,destination2,destination3\> : the potential destination nodes for the migration (the database will **not** be migrated to all of them, but to as few nodes as possibile, among these, that don't have a copy of the datas)
- --all-databases : migrate all the database in the cluster
- --start-db : start interation for all-databases from the specified database

As usual --database and --all-database are mutually exclusive


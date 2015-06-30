#
# Table structure for table 'tx_falmam_state'
#
CREATE TABLE tx_falmam_state (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    connector_name tinytext,
    config_hash tinytext,
    event_id tinytext,
    sync_id tinytext,
    sync_offset tinytext,
    notified int(11) DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

#
# Table structure for table 'tx_falmam_event_queue'
#
CREATE TABLE tx_falmam_event_queue (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    event_id int(11) DEFAULT '0' NOT NULL,
    status tinytext,
    runtime float DEFAULT '0' NOT NULL,
    object_id tinytext,
    event_type tinytext,
    target tinytext,
    skipuntil int(11) DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

#
# Table structure for table 'tx_falmam_mapping'
#
CREATE TABLE tx_falmam_mapping (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    connector_name tinytext,
    mam_field tinytext,
    fal_field tinytext,
    value_map text,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

#
# Table structure for table 'sys_file'
#
CREATE TABLE sys_file (
    tx_falmam_id tinytext
    tx_falmam_derivate_suffix tinytext
);
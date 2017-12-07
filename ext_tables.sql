#
# Table structure for table 'tx_cloudflare_domain_model_queueitem'
#
CREATE TABLE tx_cloudflare_domain_model_queueitem (
	uid int(11) NOT NULL auto_increment,
	crdate int(11) DEFAULT '0' NOT NULL,
	page_uid int(11) DEFAULT '0' NOT NULL,
	cache_command int(11) DEFAULT '0' NOT NULL,
	cache_tag varchar(40) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid)
) ENGINE=InnoDB;
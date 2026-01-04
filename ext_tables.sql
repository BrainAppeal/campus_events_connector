#
# Table structure for table 'tx_campuseventsconnector_domain_model_event'
#
CREATE TABLE tx_campuseventsconnector_domain_model_event (
	status int(11) DEFAULT '0' NOT NULL,
	canceled smallint(5) unsigned DEFAULT '0' NOT NULL,
	published smallint(5) unsigned DEFAULT '0' NOT NULL,
	completed smallint(5) unsigned DEFAULT '0' NOT NULL,
	archived smallint(5) unsigned DEFAULT '0' NOT NULL,
	url varchar(255) DEFAULT '' NOT NULL,
	name varchar(255) DEFAULT '' NOT NULL,
	subtitle varchar(255) DEFAULT '' NOT NULL,
	description text,
	short_description text,
	learning_objective text,
	min_participants int(11) DEFAULT NULL,
	max_participants int(11) DEFAULT NULL,
	categories int(11) unsigned DEFAULT '0' NOT NULL,
	organizer int(11) unsigned DEFAULT '0' NOT NULL,
	target_groups int(11) unsigned DEFAULT '0' NOT NULL,
	filter_categories int(11) unsigned DEFAULT '0' NOT NULL,
	view_lists int(11) unsigned DEFAULT '0' NOT NULL,
    alternative_events int(11) unsigned DEFAULT '0' NOT NULL,
    contact_persons int(11) unsigned DEFAULT '0' NOT NULL,
    disturber_message varchar(255) DEFAULT '' NOT NULL,
	start_tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	end_tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	event_attachments int(11) unsigned DEFAULT '0' NOT NULL,
    event_attendance_mode varchar(255) DEFAULT '' NOT NULL,
    event_images int(11) unsigned DEFAULT '0' NOT NULL,
    event_number varchar(255) DEFAULT '' NOT NULL,
    event_sessions int(11) unsigned DEFAULT '0' NOT NULL,
    event_ticket_price_variants int(11) unsigned DEFAULT '0' NOT NULL,
    external_order_email_address varchar(255) DEFAULT '' NOT NULL,
    external_order_url varchar(255) DEFAULT '' NOT NULL,
	direct_registration_url varchar(255) DEFAULT '' NOT NULL,
    locations int(11) unsigned DEFAULT '0' NOT NULL,
	modified_at_recursive int(11) DEFAULT '0',
    order_type int(11) unsigned DEFAULT '0' NOT NULL,
    referents int(11) unsigned DEFAULT '0' NOT NULL,
    referents_title varchar(255) DEFAULT '' NOT NULL,
	seo_description text,
    seo_title varchar(255) DEFAULT '' NOT NULL,
	seo_robots_index smallint(5) unsigned DEFAULT '0' NOT NULL,
	seo_robots_follow smallint(5) unsigned DEFAULT '0' NOT NULL,
    sponsors int(11) unsigned DEFAULT '0' NOT NULL,
    sponsors_title varchar(255) DEFAULT '' NOT NULL,
	slug varchar(2048),

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY path_segment (slug(185), uid),
    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_location'
#
CREATE TABLE tx_campuseventsconnector_domain_model_location (

	name text,
	street_name varchar(255) DEFAULT '' NOT NULL,
	town varchar(255) DEFAULT '' NOT NULL,
	zip_code varchar(255) DEFAULT '' NOT NULL,
	list_view_display_name varchar(255) DEFAULT '' NOT NULL,
	building varchar(255) DEFAULT '' NOT NULL,
	room varchar(255) DEFAULT '' NOT NULL,
	longitude varchar(255) DEFAULT '' NOT NULL,
	latitude varchar(255) DEFAULT '' NOT NULL,

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_organizer'
#
CREATE TABLE tx_campuseventsconnector_domain_model_organizer (

	name varchar(255) DEFAULT '' NULL,

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_timerange'
#
CREATE TABLE tx_campuseventsconnector_domain_model_timerange (

	event int(11) unsigned DEFAULT '0' NOT NULL,
	event_session int(11) unsigned DEFAULT '0' NOT NULL,

	start_tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	end_tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	start_date_is_set smallint(5) unsigned DEFAULT '0' NOT NULL,
	end_date_is_set smallint(5) unsigned DEFAULT '0' NOT NULL,

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_category'
#
CREATE TABLE tx_campuseventsconnector_domain_model_category (

	name varchar(255) DEFAULT '' NOT NULL,

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_targetgroup'
#
CREATE TABLE tx_campuseventsconnector_domain_model_targetgroup (

	name varchar(255) DEFAULT '' NOT NULL,

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_viewlist'
#
CREATE TABLE tx_campuseventsconnector_domain_model_viewlist (

	name varchar(255) DEFAULT '' NOT NULL,

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_filtercategory'
#
CREATE TABLE tx_campuseventsconnector_domain_model_filtercategory (

	name varchar(255) DEFAULT '' NOT NULL,
	parent int(11) unsigned DEFAULT '0',

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_contactperson'
#
CREATE TABLE tx_campuseventsconnector_domain_model_contactperson (

	title varchar(255) DEFAULT '' NOT NULL,
	first_name varchar(255) DEFAULT '' NOT NULL,
	last_name varchar(255) DEFAULT '' NOT NULL,
	position varchar(255) DEFAULT '' NOT NULL,
	department varchar(255) DEFAULT '' NOT NULL,
	institution varchar(255) DEFAULT '' NOT NULL,
	phone varchar(255) DEFAULT '' NOT NULL,
	mail_address varchar(255) DEFAULT '' NOT NULL,

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_event_attachment'
#
CREATE TABLE tx_campuseventsconnector_domain_model_eventattachment (
	name varchar(255) DEFAULT '' NOT NULL,
	file_hash text,
	attachment_file int(11) unsigned DEFAULT '0',

	event int(11) unsigned DEFAULT '0' NOT NULL,

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_eventimage'
#
CREATE TABLE tx_campuseventsconnector_domain_model_eventimage (

	name varchar(255) DEFAULT '' NOT NULL,
	file_hash text,
	image_file int(11) unsigned DEFAULT '0',

	event int(11) unsigned DEFAULT '0' NOT NULL,

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_eventsession'
#
CREATE TABLE tx_campuseventsconnector_domain_model_eventsession (

	start_tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	end_tstamp int(11) unsigned DEFAULT '0' NOT NULL,

	event int(11) unsigned DEFAULT '0' NOT NULL,
	session_time_periods int(11) unsigned DEFAULT '0' NOT NULL,

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)
);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_eventticketpricevariant'
#
CREATE TABLE tx_campuseventsconnector_domain_model_eventticketpricevariant (

	bookable_from datetime DEFAULT '1900-01-01 00:00:00',
	bookable_till datetime DEFAULT '1900-01-01 00:00:00',
	name varchar(255) DEFAULT '' NOT NULL,
	pv_quota INT DEFAULT NULL,
	pv_price NUMERIC(10, 2) DEFAULT NULL,
	pv_tax_rate NUMERIC(10, 2) DEFAULT NULL,
	pv_tax NUMERIC(10, 2) DEFAULT NULL,
	direct_checkout_url varchar(255) DEFAULT '' NOT NULL,
	price_category int(11) unsigned DEFAULT '0',
	event int(11) unsigned DEFAULT '0',

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_pricecategory'
#
CREATE TABLE tx_campuseventsconnector_domain_model_pricecategory (

	name varchar(255) DEFAULT '' NOT NULL,

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_referent'
#
CREATE TABLE tx_campuseventsconnector_domain_model_referent (

	title varchar(255) DEFAULT '' NOT NULL,
	first_name varchar(255) DEFAULT '' NOT NULL,
	last_name varchar(255) DEFAULT '' NOT NULL,
	external_url varchar(255) DEFAULT '' NOT NULL,
	academic_degree varchar(255) DEFAULT '' NOT NULL,
	institution varchar(255) DEFAULT '' NOT NULL,
	phone varchar(255) DEFAULT '' NOT NULL,
	email varchar(255) DEFAULT '' NOT NULL,
	type smallint(5) unsigned DEFAULT '0' NOT NULL,
	business_address text,
	publications text,
	focus_of_work text,
	event_formats text,
	references text,
	description text,

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_sponsor'
#
CREATE TABLE tx_campuseventsconnector_domain_model_sponsor (

	name varchar(255) DEFAULT '' NOT NULL,
	url varchar(255) DEFAULT '' NOT NULL,
	image_hash text,
    image_file int(11) unsigned DEFAULT '0',

	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL,
	data_hash varchar(128) NOT NULL DEFAULT '',

    KEY import (ce_import_id,ce_import_source)

);

#
# Table structure for table 'tx_campuseventsconnector_domain_model_convertconfiguration'
#
CREATE TABLE tx_campuseventsconnector_domain_model_convertconfiguration (

	target_pid int(11) DEFAULT '0' NOT NULL,
	template_path varchar(255) DEFAULT '' NOT NULL,
	target_groups int(11) unsigned DEFAULT '0' NOT NULL,
	filter_categories int(11) unsigned DEFAULT '0' NOT NULL,
	view_lists int(11) unsigned DEFAULT '0' NOT NULL,

	type varchar(100) NOT NULL DEFAULT '0',

);

#
# Table structure for table 'tx_campuseventsconnector_convertconf_filtercategory_mm'
#
CREATE TABLE tx_campuseventsconnector_convertconf_filtercategory_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_campuseventsconnector_convertconf_targetgroup_mm'
#
CREATE TABLE tx_campuseventsconnector_convertconf_targetgroup_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_campuseventsconnector_convertconf_viewlist_mm'
#
CREATE TABLE tx_campuseventsconnector_convertconf_viewlist_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_campuseventsconnector_event_category_mm'
#
CREATE TABLE tx_campuseventsconnector_event_category_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_campuseventsconnector_event_organizer_mm'
#
CREATE TABLE tx_campuseventsconnector_event_organizer_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_campuseventsconnector_event_filtercategory_mm'
#
CREATE TABLE tx_campuseventsconnector_event_filtercategory_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_campuseventsconnector_event_targetgroup_mm'
#
CREATE TABLE tx_campuseventsconnector_event_targetgroup_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_campuseventsconnector_event_viewlist_mm'
#
CREATE TABLE tx_campuseventsconnector_event_viewlist_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_campuseventsconnector_event_alternative_event_mm'
#
CREATE TABLE tx_campuseventsconnector_event_alternative_event_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_campuseventsconnector_event_contactperson_mm'
#
CREATE TABLE tx_campuseventsconnector_event_contactperson_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_campuseventsconnector_event_location_mm'
#
CREATE TABLE tx_campuseventsconnector_event_location_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_campuseventsconnector_event_ordertype_mm'
#
CREATE TABLE tx_campuseventsconnector_event_ordertype_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_campuseventsconnector_event_referent_mm'
#
CREATE TABLE tx_campuseventsconnector_event_referent_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_campuseventsconnector_event_sponsor_mm'
#
CREATE TABLE tx_campuseventsconnector_event_sponsor_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'sys_file_reference'
#
CREATE TABLE sys_file_reference (
	ce_import_source varchar(255) DEFAULT NULL,
	ce_import_id int(11) unsigned DEFAULT NULL ,
	ce_imported_at int(11) unsigned DEFAULT NULL ,
);


#
# Table structure for table 'tx_campuseventsconnector_import'
#
CREATE TABLE tx_campuseventsconnector_import (
  uid int(11) NOT NULL auto_increment,
	pid int(10) unsigned  DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	deleted smallint(5) unsigned DEFAULT '0' NOT NULL,
	hidden smallint(5) unsigned DEFAULT '0' NOT NULL,
  running tinyint(4) DEFAULT '0' NOT NULL,
  import_start int(11) DEFAULT '0' NOT NULL,
  import_end int(11) DEFAULT '0' NOT NULL,
  import_source varchar(64) NOT NULL DEFAULT '',
  total_row_count int(10) unsigned  DEFAULT '0' NOT NULL,
  full_loaded_row_count int(10) unsigned  DEFAULT '0' NOT NULL,
  imported_row_count int(10) unsigned NOT NULL,
  skipped_row_count int(10) unsigned DEFAULT '0' NOT NULL,
  import_limit int(10) unsigned NOT NULL,
  import_source_modified_at int(11) DEFAULT '0' NOT NULL,
	first_import_done smallint(5) unsigned DEFAULT '0' NOT NULL,

  KEY first_import_done (first_import_done),
  PRIMARY KEY (uid)
);

#
# Table structure for table 'tx_campuseventsconnector_import_row'
#
CREATE TABLE tx_campuseventsconnector_import_row (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  crdate int(11) DEFAULT '0' NOT NULL,
  deleted tinyint(4) DEFAULT '0' NOT NULL,
  import_id int(11) DEFAULT '0' NOT NULL,
  import_data mediumtext,
  source_type varchar(128) DEFAULT '0' NOT NULL,
  source_record_identifier varchar(64) DEFAULT '0' NOT NULL,
  source_record_uid int(11) DEFAULT '0' NOT NULL,
  target_record_uid int(11) DEFAULT '0' NOT NULL,
  data_fully_loaded tinyint(4) DEFAULT '0' NOT NULL,
  data_processed tinyint(4) DEFAULT '0' NOT NULL,
  files_processed tinyint(4) DEFAULT '0' NOT NULL,
  import_skipped tinyint(4) DEFAULT '0' NOT NULL,
	unchanged tinyint(4) DEFAULT '0' NOT NULL,
  data_hash varchar(128) NOT NULL DEFAULT '',
	last_updated int(11) DEFAULT '0' NOT NULL,
  priority smallint unsigned DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
  KEY idx_target_record (source_type, target_record_uid),
  KEY idx_import_id (import_id),
  KEY idx_data_hash (data_hash),
  KEY idx_source_record (source_type, source_record_uid),
  KEY idx_source_record_str (source_type, source_record_identifier),
  KEY idx_import_source_type (import_id, source_type),
  KEY idx_import_skipped_data_processed (import_id, import_skipped, data_processed),
  KEY idx_import_skipped_files_processed (import_id, import_skipped, files_processed),
  KEY idx_skip_by_hash_lookup (import_id, source_type, source_record_uid, data_hash)
);

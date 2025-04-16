#
# Table structure for table 'tx_indexnow_url'
#
CREATE TABLE tx_indexnow_stack
(
	url text,
	url_hash CHAR(40) NOT NULL,
    UNIQUE KEY unique_url_hash (url_hash)
);

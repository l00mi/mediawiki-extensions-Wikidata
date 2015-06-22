	CREATE TABLE IF NOT EXISTS /*_*/wbq_evaluation (
			  special_page_id               int               NOT NULL,
			  entity_id                     VARCHAR(15)       NOT NULL,
			  insertion_timestamp           int               NOT NULL,
			  reference_timestamp           int               DEFAULT NULL,
			  result_string                 TEXT              DEFAULT NULL
			) /*$wgDBTableOptions*/;
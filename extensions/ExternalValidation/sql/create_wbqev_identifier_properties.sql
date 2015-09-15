CREATE TABLE IF NOT EXISTS /*_*/wbqev_identifier_properties (
  identifier_pid  VARBINARY(15)  NOT NULL,
  dump_id         VARBINARY(25)  NOT NULL,
  PRIMARY KEY (identifier_pid, dump_id)
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/dump_id ON /*_*/wbqev_identifier_properties (dump_id);
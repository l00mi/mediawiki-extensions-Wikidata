CREATE TABLE IF NOT EXISTS /*_*/wbqev_external_data (
  row_id          BIGINT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  dump_id         VARBINARY(25)    NOT NULL,
  external_id     VARBINARY(100)   NOT NULL,
  pid             VARBINARY(15)    NOT NULL,
  external_value  VARBINARY(255)   NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/dump_id_external_id ON /*_*/wbqev_external_data (dump_id, external_id);

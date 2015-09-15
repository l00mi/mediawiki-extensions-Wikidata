CREATE TABLE IF NOT EXISTS /*_*/wbqev_dump_information (
  id           VARBINARY(25)   PRIMARY KEY NOT NULL,
  source_qid   VARBINARY(15)   NOT NULL,
  import_date  CHAR(14)        NOT NULL,
  language     VARBINARY(10)   NOT NULL,
  source_url   VARBINARY(300)  UNIQUE NOT NULL,
  size         INT UNSIGNED    NOT NULL,
  license_qid  VARBINARY(15)   NOT NULL
) /*$wgDBTableOptions*/;
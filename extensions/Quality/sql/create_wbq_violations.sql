CREATE TABLE IF NOT EXISTS /*_*/wbq_violations (
  entity_id                     VARBINARY(15)     NOT NULL,
  pid                           VARBINARY(15)     NOT NULL,
  claim_guid                    VARBINARY(63)     NOT NULL,
  constraint_id                 VARBINARY(63)     NOT NULL,
  constraint_type_entity_id     VARBINARY(15)     NOT NULL,
  additional_info               TEXT              DEFAULT NULL,
  updated_at                    VARBINARY(31)     NOT NULL,
  revision_id                   INT(10) UNSIGNED  NOT NULL,
  status                        VARBINARY(31)     NOT NULL,
  PRIMARY KEY (claim_guid, constraint_id)
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/claim_guid ON /*_*/wbq_violations (claim_guid);
CREATE INDEX /*i*/constraint_id ON /*_*/wbq_violations (constraint_id);
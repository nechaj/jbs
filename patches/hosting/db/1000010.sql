CREATE DEFINER = CURRENT_USER TRIGGER `DomainsSchemesOnInsert` BEFORE INSERT ON `DomainsSchemes`
  FOR EACH ROW BEGIN
    SET NEW.`CreateDate` = UNIX_TIMESTAMP();
  END;
ALTER TABLE `Users` DROP FOREIGN KEY `UsersOwnerID`;
-- SEPARATOR
ALTER TABLE `Users` CHANGE `OwnerID` `OwnerID` int(11) NULL;
-- SEPARATOR
ALTER TABLE `Users` ADD CONSTRAINT `UsersOwnerID` FOREIGN KEY (`OwnerID`) REFERENCES `Users` (`ID`) ON DELETE SET NULL ON UPDATE CASCADE;
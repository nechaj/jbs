ALTER TABLE `Users` ADD `IsInheritGroup` enum('no','yes') default 'no' AFTER `IsManaged`;
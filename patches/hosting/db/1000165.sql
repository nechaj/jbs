UPDATE `Orders` SET `IsPayed` = 'yes' WHERE `ID` IN (SELECT `OrderID` FROM `HostingOrders` WHERE `HostingOrders`.`OrderID` = `Orders`.`ID` AND `StatusID` != 'Waiting');
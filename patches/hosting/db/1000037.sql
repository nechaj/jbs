ALTER TABLE `HostingOrders` ADD FOREIGN KEY (`ServerID`) REFERENCES `HostingServers` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;
UPDATE `Contracts` SET `StatusID` = 'OnForming' WHERE (SELECT `StatusID` FROM `Profiles` WHERE `Profiles`.`ID` = `Contracts`.`ProfileID`) = 'OnFilling';
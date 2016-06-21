ALTER TABLE `Persons` ADD INDEX PINDEX(`id`);
ALTER TABLE `RanksAverage` ADD INDEX PINDEX(`personId`);
ALTER TABLE `RanksSingle` ADD INDEX PINDEX(`personId`);
ALTER TABLE `Countries` ADD INDEX CINDEX(`id`);

ALTER TABLE `Persons` ADD INDEX PINDEX(`id`);
ALTER TABLE `RanksAverage` ADD INDEX PINDEX(`personId`);
ALTER TABLE `RanksSingle` ADD INDEX PINDEX(`personId`);

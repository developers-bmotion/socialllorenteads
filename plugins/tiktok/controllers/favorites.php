<?php
defined('ALTUMCODE') || die();

$source_users = [];

$result = $database->query("SELECT `tiktok_users`.* FROM `tiktok_users` LEFT JOIN `favorites` ON `favorites`.`source_user_id` = `tiktok_users`.`id` WHERE `favorites`.`user_id` = {$account_user_id} AND `source` = 'TIKTOK'");

while($row = $result->fetch_object()) $source_users[] = $row;

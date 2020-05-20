<?php
defined('ALTUMCODE') || die();

$source_users = [];

$result = $database->query("
    SELECT  
        `unlocked_reports`.`date`, 
        `unlocked_reports`.`source_user_id`,
        `unlocked_reports`.`user_id`, 
        `unlocked_reports`.`expiration_date`, 
        `tiktok_users`.`username`, 
        `tiktok_users`.`name`, 
        `tiktok_users`.`likes`,
        `tiktok_users`.`followers`,
        `tiktok_users`.`profile_picture_url`
    FROM `unlocked_reports` 
    LEFT JOIN `tiktok_users` ON `unlocked_reports`.`source_user_id` = `tiktok_users`.`id` 
    WHERE 
        `user_id` = {$account_user_id}
        AND `source` = 'TIKTOK'
");

while($row = $result->fetch_object()) $source_users[] = $row;

$source_users_csv = csv_exporter($source_users, ['date', 'source_user_id', 'user_id', 'expiration_date']);

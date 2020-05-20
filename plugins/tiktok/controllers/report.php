<?php
defined('ALTUMCODE') || die();

if ($user[0] !== '@') {
    $user = '@'.$user;
}
/* We need to check if the user already exists in our database */
$source_account = Database::get('*', 'tiktok_users', ['username' => $user]);

if( $refresh || !$source_account || ($source_account && (new \DateTime())->modify('-'.$settings->facebook_check_interval.' hours') > (new \DateTime($source_account->last_check_date)))) {


    $tiktok = new TikTok();


    /* Set proxy if needed */
    if($is_proxy_request) {
        $tiktok::set_proxy($is_proxy_request);
    }

    try {
        $source_account_data = $tiktok->get($user);
    } catch (Exception $error) {
        $_SESSION['error'][] = $error->getCode() == 404 ? $language->facebook->report->error_message->not_found : $error->getMessage();

        /* Make sure to set the failed request to the proxy */
        if($is_proxy_request) {
            if($error->getCode() == 404) {
                $database->query("UPDATE `proxies` SET `failed_requests` = `failed_requests` + 1, `total_failed_requests` = `total_failed_requests` + 1, `last_date` = '{$date}' WHERE `proxy_id` = {$proxy->proxy_id}");
            } else {
                $database->query("UPDATE `proxies` SET `last_date` = '{$date}' WHERE `proxy_id` = {$proxy->proxy_id}");
            }
        }

        redirect();
    }


    /* Make sure to set the successful request to the proxy */
    if($is_proxy_request) {
        if($proxy->failed_requests >= $settings->proxy_failed_requests_pause) {
            Database::update('proxies', ['failed_requests' => 0, 'successful_requests' => 1, 'last_date' => $date], ['proxy_id' => $proxy->proxy_id]);
        } else {
            $database->query("UPDATE `proxies` SET `successful_requests` = `successful_requests` + 1, `total_successful_requests` = `total_successful_requests` + 1, `last_date` = '{$date}' WHERE `proxy_id` = {$proxy->proxy_id}");
        }

    }



    /* Vars to be added & used */
    $source_account_new = new StdClass();
    $source_account_new->username = $user;
    $source_account_new->name = $source_account_data->userData->nickName;
    $source_account_new->likes = $source_account_data->userData->heart;
    $source_account_new->followers = $source_account_data->userData->fans;
    $source_account_new->profile_picture_url = $source_account_data->userData->coversMedium[0];
    $source_account_new->is_verified = (int) $source_account_data->userData->verified;
    $source_account_new->details = base64_encode($source_account_data->userData->signature);
    $source_account_new->following = $source_account_data->userData->following;
    $source_account_new->video = $source_account_data->userData->video;


    // echo "<pre>";
    // print_r($source_account_new);
    // echo "</pre>";
    //die();

    /*
    /* Get extra details from last media * /
    $details = [
        'type'      => $source_account_data->type,
    ];
    $details = json_encode($details);
    */

    /* Insert into db */
    /* Insert into db */
    $stmt = $database->prepare("INSERT INTO `tiktok_users` (
        `username`,
        `name`,
        `heart`,
        `fans`,
        `details`,
        `profile_picture_url`,
        `verified`,
        `added_date`,
        `last_check_date`,
        `last_successful_check_date`,
        `following`,
        `video`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        `username` = VALUES (username),
        `name` = VALUES (name),
        `heart` = VALUES (heart),
        `fans` = VALUES (fans),
        `profile_picture_url` = VALUES (profile_picture_url),
        `verified` = VALUES (verified),
        `last_check_date` = VALUES (last_check_date),
        `last_successful_check_date` = VALUES (last_successful_check_date),
        `following` = VALUES (following),
        `video` = VALUES (video)
    ");

    $stmt->bind_param('ssssssssssss',
        $source_account_new->username,
        $source_account_new->name,
        $source_account_new->likes,
        $source_account_new->followers,
        $source_account_new->details,
        $source_account_new->profile_picture_url,
        $source_account_new->is_verified,
        $date,
        $date,
        $date,
        $source_account_new->following,
        $source_account_new->video
    );
    $data = $stmt->execute();
    $stmt->close();

    //echo json_encode($data);


    /* Retrieve the just created / updated row */
    $source_account = Database::get('*', 'tiktok_users', ['username' => $user]);


    /* Update or insert the check log */
    $log = $database->query("SELECT `id` FROM `tiktok_logs` WHERE `tiktok_user_id` = '{$source_account->id}' AND DATEDIFF('{$date}', `date`) = 0")->fetch_object();


    if($log) {
        Database::update(
            'tiktok_logs',
            [
                'likes' => $source_account->heart,
                'fans' => $source_account->fans,
                'date' => $date
            ],
            ['id' => $log->id]
        );
    } else {
        $stmt = $database->prepare("INSERT INTO `tiktok_logs` (
            `tiktok_user_id`,
            `username`,
            `likes`,
            `fans`,
            `video`,
            `date`
        ) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss',
            $source_account->id,
            $source_account->username,
            $source_account->heart,
            $source_account->fans,
            $source_account->video,
            $date
        );
        $stmt->execute();
        $stmt->close();
    }

}

/* Retrieve last X entries */
$logs = [];

if($date_start && $date_end) {
    $date_start_query = (new \DateTime($date_start))->format('Y-m-d H:i:s');
    $date_end_query = (new \DateTime($date_end))->modify('+1 day')->format('Y-m-d H:i:s');

    $logs_result = $database->query("SELECT * FROM `tiktok_logs` WHERE `tiktok_user_id` = '{$source_account->id}' AND (`date` BETWEEN '{$date_start_query}' AND '{$date_end_query}')  ORDER BY `date` DESC");
} else {
    $logs_result = $database->query("SELECT * FROM `tiktok_logs` WHERE `tiktok_user_id` = '{$source_account->id}' ORDER BY `date` DESC LIMIT 15");
}


while($log = $logs_result->fetch_assoc()) { $logs[] = $log; }
$logs = array_reverse($logs);

/* Generate data for the charts and retrieving the average followers /uploads per day */
$logs_chart = [
    'labels'                    => [],
    'likes'                     => [],
    'followers'                   => [],
];

$total_new = [
    'likes' => [],
    'followers' => []
];

for($i = 0; $i < count($logs); $i++) {
    $logs_chart['labels'][] = (new \DateTime($logs[$i]['date']))->format($language->global->date->datetime_format);
    $logs_chart['likes'][] = $logs[$i]['likes'];
    $logs_chart['followers'][] = $logs[$i]['fans'];

    if($i != 0) {
        $total_new['likes'][] = $logs[$i]['likes'] - $logs[$i - 1]['likes'];
        $total_new['followers'][] = $logs[$i]['fans'] - $logs[$i - 1]['fans'];
    }
}

/* reverse it back */
$logs = array_reverse($logs);

/* Defining the chart data */
$logs_chart = generate_chart_data($logs_chart);

/* Defining the future projections data */
$total_days = count($logs) > 1 ? (new \DateTime($logs[count($logs)-1]['date']))->diff((new \DateTime($logs[1]['date'])))->format('%a') : 0;

$average = [
    'likes'                 => $total_days > 0 ? (int) ceil(array_sum($total_new['likes']) / $total_days) : 0,
    'followers'    => $total_days > 0 ? (int) ceil((array_sum($total_new['followers']) / $total_days)) : 0
];


$source_account->details = base64_decode($source_account->details);

/* Custom title */
add_event('title', function() {
    global $page_title;
    global $user;
    global $language;

    $page_title = sprintf($language->tiktok->report->title, $user);
});

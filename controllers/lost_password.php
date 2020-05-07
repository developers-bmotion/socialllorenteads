<?php
defined('ALTUMCODE') || die();
User::logged_in_redirect();

$email = '';

/* Initiate captcha */
$captcha = new Captcha($settings->recaptcha, $settings->public_key, $settings->private_key);

if(!empty($_POST)) {
    /* Clean the posted variable */
    $_POST['email'] = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $email = $_POST['email'];

    /* Check for any errors */
    if(!$captcha->is_valid()) {
        $_SESSION['error'][] = $language->global->error_message->invalid_captcha;
    }

    /* If there are no errors, resend the activation link */
    if(empty($_SESSION['error'])) {

        if($this_account = Database::get(['user_id', 'email', 'name', 'username'], 'users', ['email' => $_POST['email']])) {
            /* Define some variables */
            $lost_password_code = md5($_POST['email'] . microtime());

            /* Update the current activation email */
            $database->query("UPDATE `users` SET `lost_password_code` = '{$lost_password_code}' WHERE `user_id` = {$this_account->user_id}");

            /* Prepare the email */
            $email_template = generate_email_template(
                [
                    '{{NAME}}' => $this_account->name,
                    '{{WEBSITE_TITLE}}' => $settings->title
                ],
                $settings->lost_password_email_template_subject,
                [
                    '{{LOST_PASSWORD_LINK}}' => $settings->url . 'reset-password/' . $_POST['email'] . '/' . $lost_password_code,
                    '{{NAME}}' => $this_account->name,
                    '{{ACCOUNT_USERNAME}}' => $this_account->username,
                    '{{WEBSITE_TITLE}}' => $settings->title
                ],
                $settings->lost_password_email_template_body
            );

            /* Send the email */
            sendmail($this_account->email, $email_template->subject, $email_template->body);

        }

        /* Set success message */
        $_SESSION['success'][] = $language->lost_password->notice_message->success;
    }


}

/* Insert the recaptcha library */
add_event('head', function() {
    echo '<script src="https://www.google.com/recaptcha/api.js"></script>';
});

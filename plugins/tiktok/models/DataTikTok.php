<?php
namespace plugins\tiktok\models;

class DataTikTok
{
    public $user = null;
    public $is_verified;

    public function __construct()
    {
        $this->user = new UserTikTok();
    }
}

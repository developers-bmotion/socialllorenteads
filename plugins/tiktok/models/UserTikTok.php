<?php
namespace plugins\tiktok\models;

class UserTikTok
{
    public $name = '';
    public $secUid = '';
    public $userId = '';
    public $isSecret = 0;
    public $uniqueId = '';
    public $nickName = '';
    public $signature = '';
    public $covers = [];
    public $coversMedium = [];
    public $following = 0;
    public $fans = 0;
    public $heart = 0;
    public $video = 0;
    public $verified = 0;
    public $digg = 0;
    public $ftc = 0;
    public $relation = -1;
    public $openFavorite = 0;
}

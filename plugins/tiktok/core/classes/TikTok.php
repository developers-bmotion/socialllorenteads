<?php
use Unirest\Request;

class TikTok {

    private $api_url = 'https://www.tiktok.com/';
    private $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36';


    public function __construct() {


        if(!function_exists('curl_version')) {

            throw new Exception('Your webhost does not support curl and we cannot continue with the request.');

        }

    }

    public function set_user_agent($user_agent) {
        return $this->user_agent = $user_agent;
    }

    public static function set_proxy(array $config) {
        $default_config = [
            'port' => false,
            'tunnel' => false,
            'address' => false,
            'type' => CURLPROXY_HTTP,
            'timeout' => false,
            'auth' => [
                'user' => '',
                'pass' => '',
                'method' => CURLAUTH_BASIC
            ]
        ];

        $config = array_replace($default_config, $config);

        Request::proxy($config['address'], $config['port'], $config['type'], $config['tunnel']);

        if(isset($config['auth'])) {
            Request::proxyAuth($config['auth']['user'], $config['auth']['pass'], $config['auth']['method']);
        }

        if(isset($config['timeout'])) {
            Request::timeout((int) $config['timeout']);
        }
    }

    public function generate_headers() {
        $headers = [
//            'cache-control'             => 'max-age=0',
//            'upgrade-insecure-requests' => '1',
//            'accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
//            'accept-encoding'           => 'gzip, deflate, br',
            'accept-language'           => 'en-US,en;q=0.9,fr;q=0.8,ro;q=0.7,ru;q=0.6,la;q=0.5,pt;q=0.4,de;q=0.3'
        ];

        $headers['user-agent'] = $this->user_agent;

        return $headers;
    }

    /* Function to get the content of the requested url with a specific function */
    public function get($path) {

        $url = $this->api_url . $path;
        $response = Request::get($url, $this->generate_headers());
        return $this->parse($response->raw_body);
    }

    public function get_meta_content($content, $property) {

        preg_match('/<meta property="' . $property . '" content="([^"]+)"/', $content, $match, PREG_OFFSET_CAPTURE);

        if($match && count($match) > 1) {

            return $match[1][0];

        } else {

            throw new Exception('We could not get all the details about the page properly.');

        }


    }

    public function parse($raw_body) {

        $data = explode('__NEXT_DATA__', $raw_body)[1];
        $data = explode('</script>', $data)[0];
        $data = preg_replace('/.+props/', '{"props', $data);

        //echo($data);
            $data = json_decode($data);
            /*
        unset($data->query);
        echo "<pre>";
        print_r($data);
        echo(json_encode($data->props->pageProps->userData));



        die();
        */

        $response = new StdClass();



        if(!$data || !$data->props || !$data->props->pageProps || !$data->props->pageProps->userData) {
            throw new Exception('Account with given username does not exist.', 404);
        }


        $response->userData = $data->props->pageProps->userData;

        $response->is_verified = $data->props->pageProps->userData->verified == 1;

        return $response;
    }

}

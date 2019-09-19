<?php

namespace Bifs\Services;

class Logger {
    private const BASE_URL = 'http://logger.bifs.me';

    public $project;
    public $service;
    public $auth_key;
    public $auth_secret;

    public function __construct (array $config) {
        if (isset($config['project']) && is_string($config['project'])) {
            $project = trim($config['project']);

            if (strlen($project) > 0) {
                $this->project = $project;
            }
        }

        if (isset($config['service']) && is_string($config['service'])) {
            $service = trim($config['service']);

            if (strlen($service) > 0) {
                $this->service = $service;
            }
        }

        if (isset($config['auth']) && is_array($config['auth'])) {
            $auth = $config['auth'];

            if (isset($auth['key']) && is_string($auth['key'])) {
                $auth_key = trim($auth['key']);

                if (strlen($auth_key)) {
                    $this->auth_key = $auth_key;
                }
            }

            if (isset($auth['secret']) && is_string($auth['secret'])) {
                $auth_secret = trim($auth['secret']);

                if (strlen($auth_secret)) {
                    $this->auth_secret = $auth_secret;
                }
            }
        }

        if (empty($this->project)) {
            throw new \Exception('ERROR:UNDEFINED_PROJECT');
        }

        if (empty($this->service)) {
            throw new \Exception('ERROR:UNDEFINED_SERVICE');
        }

        if (empty($this->auth_key)) {
            throw new \Exception('ERROR:AUTH_KEY_NOT_SET');
        }

        if (empty($this->auth_secret)) {
            throw new \Exception('ERROR:AUTH_SECRET_NOT_SET');
        }
    }

    private function sendRequest (array $postdata) {
        $bifs_request = $this->project . ':' . $this->service;
        $authorization = base64_encode($this->auth_key . ':' . $this->auth_secret);
        $data = json_encode($postdata);
        $headers = array(
            'Bifs-Request: ' . $bifs_request,
            'Authorization: Bearer ' . $authorization,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, static::BASE_URL . '/logs');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $output = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorno = curl_errno($ch);

        curl_close($ch);

        if ($errorno > 0) {
            throw new \Exception('ERROR:REQUEST_ERROR');
        }

        $output = @json_decode($output);
        
        if ($http_code !== 200) {
            if ($output && $output->error) {
                throw new \Exception($output->error);
            }

            throw new \Exception('ERROR:RESPONSE_ERROR');
        }

        return $output;
    }

    public function capture ($eventName, $eventData = null, $eventCategory = null) {
        $postdata = array();

        if (is_string($eventName)) {
            $eventName = trim($eventName);
        } else {
            $eventName = '';
        }

        if (strlen($eventName) <= 0) {
            throw new \Exception('ERROR:INVALID_EVENT_NAME');
        }

        $postdata['name'] = $eventName;

        if (is_string($eventData) || is_numeric($eventData)) {
            if (is_string($eventData)) {
                $eventData = trim($eventData);

                if (strlen($eventData) <= 0) {
                    $eventData = null;
                }
            }

            if ($eventData !== null) {
                $postdata['data'] = $eventData;
            }
        }

        if (is_string($eventCategory)) {
            $eventCategory = trim($eventCategory);

            if (strlen($eventCategory) > 0) {
                $postdata['category'] = $eventCategory;
            }
        }

        return function ($vars = null, $channel = null) use ($postdata) {
            $post_vars = array();

            if (is_array($vars) && count($vars) > 0) {
                foreach ($vars as $key => $value) {
                    if (is_string($key)) {
                        $key = trim($key);
                    }

                    if (is_string($value)) {
                        $value = trim($value);

                        if (strlen($value) <= 0) {
                            $value = null;
                        }
                    }

                    if (strlen($key) > 0 && (is_string($value) || is_numeric($value))) {
                        $post_vars[$key] = $value;
                    }
                }
            }

            if (count($post_vars) > 0) {
                $postdata['vars'] = $post_vars;
            }

            if (is_string($channel)) {
                $channel = trim($channel);

                if (strlen($channel) > 0) {
                    $postdata['channel'] = $channel;
                }
            }

            return $this->sendRequest($postdata);
        };
    }
}
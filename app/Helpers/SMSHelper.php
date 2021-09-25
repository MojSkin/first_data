<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Hash;
use SoapClient;

class SMSHelper
{
    private $username;
    private $password;
    private $link;
    private $client;

    /**
     *
     * Prepare a soap client with given parameters (which you passed directly to the class when you make a NEW OBJECT
     * or setting them in .env or sms.php config file)
     *
     * Attention: If soap client extension is not installed / activated in you php.ini config file, you will get an exception error
     * with this message: SMS Helper: Soap extension is not installed.
     * In this case, you only need open php.ini file (in your php folder with php version number), then look forward to
     * a line with ";extension=soap" command, remove ";", save file and restart server (Apache, Artisan or whatever you use),
     * then reload project index url.
     *
     * @param null $username // SMS Panel "username". You can send this directly or set it in .env or sms.php config file
     * @param null $password // SMS Panel "password". You can send this directly or set it in .env or sms.php config file
     * @param null $link // SMS Panel "api url". You can send this directly or set it in .env or sms.php config file
     * @throws \SoapFault
     */
    public function __construct($username=null, $password=null, $link=null) {
        $this->username = $username ?? config('sms.username');
        $this->password = $password ?? config('sms.password');
        $this->link = $link ?? config('sms.link');
        if (!extension_loaded('soap')) {
            throw new \Error('SMS Helper: Soap extension is not installed.');
        }
        return $this->client = new SoapClient($this->link);
    }

    /**
     * Send single message method.
     * Returns an array with clear message text and result state,
     * If you get TRUE in "status", it means your message is sent successfully and the message id provided in "code".
     * You can store it to use it in future like getting it`s status and current state.
     *
     * If you get FALSE in "Status", it means your message is not sent and error code is stored in "code" which you can send it to your SMS-Panel Support
     * or handle it manually, also a clear message based on SMS Panel Documentation is provided in "message"
     * you can use it to show to the front-user or SMS-Panel Support.
     *
     * @param string $fromNum // Origin sending number, etc. 5000123456
     * @param array $toNum // Array of recipient numbers (as string), etc. ["09121234567", "09361234567", ...] MAX RECIPIENTS ARE 150
     * @param string $content // A text you want to send as Short Message
     * @param bool $is_flash // Specify your message is in FLASH mode or not (default is NOT!)
     * @return array
     * [
     *    'status' => Sending message is successful or not
     *    'message' => A clear message based on SMS Panel Documentation
     *    'code' => If STATUS is true, it contains an array of sent message id pointing to each number, else you have a code returns by API, and it represents the "message" by code provided in SMS Panel Documentation
     * ]
     */
    public function sendSMS(string $fromNum, array $toNum, string $content, bool $is_flash=false) {
        $states = [
            -1 => 'Unidentified answer from AP',
            0 => 'Invalid username or password',
            1 => 'Insufficient credit',
            2 => 'Invalid sender number',
            4 => 'Sending function is disabled',
            5 => 'User is inactive',
            6 => 'Panel is expired',
            7 => 'Content text is empty',
            9 => 'No recipients are entered',
            10 => 'Time limitation of using public numbers',
            11 => 'Unexpected error, call panel admin',
            16 => 'Recipients count is more than 60',
        ];
        $client = $this->client->SendSMS(
            $fromNum,
            $toNum,
            $content,
            $is_flash?1:0,
            $this->username,
            $this->password
        );
        $code = isset($client[0])?(is_string($client[0])?(int) $client[0]:null):-1;
        $result = [
            'status' => false,
            'message' => 'Error while processing request!',
            'code' => $code,
        ];
        if (in_array($code, [0, 1, 2, 4, 5, 6, 7, 9, 10, 11, 16, -1])) {
            $result = [
                'message' => $states[$code],
                'code' => $code,
            ];
        } else {
            $codes = [];
            foreach ($client as $index => $c) {
                $codes[] = [$toNum[$index] => $c];
            }
            $result = [
                'status' => true,
                'message' => 'Message(s) sent successfully',
                'code' => $codes,
            ];
        }
        return $result;
    }

    /**
     * Send multi message as peer to peer (P2P)
     * As 1st parameter you must provide an array of Senders (count must be as much as recipients)
     * 2nd parameter is an array of recipients
     * 3rd parameter is an array of text content (each message will go for the same index of recipient)
     * 4th parameter is an array of boolean (true/false) value which indicates each row of recipients will have Flash message or not
     *
     * Attention: All arrays must be in same length.
     * Each index of each array will summarize a unique box of parameters
     *
     * @param array $fromNum // Array of origin sending numbers, etc. ["5000123456", "30001234", ...] MAX SENDERS ARE 60
     * @param array $toNum // Array of recipient numbers (as string), etc. ["09121234567", "09361234567", ...] MAX RECIPIENTS ARE 60
     * @param array $content // An array of text you want to send as Short Message
     * @param array $is_flash // Specify your message is in FLASH mode or not (default is NOT!)
     * @return array
     * [
     *    'status' => Sending message is successful or not
     *    'message' => A clear message based on SMS Panel Documentation
     *    'code' => If STATUS is true, it contains an array of sent message id (or error code) pointing to each number, else you have a code returns by API, and it represents the "message" by code provided in SMS Panel Documentation
     * ]
     */
    public function sendMultiSMS(array $fromNum, array $toNum, array $content, array $is_flash) {
        $states = [
            -1 => 'Unidentified answer from AP',
            0 => 'Invalid username or password',
            1 => 'Insufficient credit',
            2 => 'Invalid sender number',
            4 => 'Sending function is disabled',
            5 => 'User is inactive',
            6 => 'Panel is expired',
            7 => 'Content text is empty',
            9 => 'No recipients are entered',
            10 => 'Time limitation of using public numbers',
            11 => 'Unexpected error, call panel admin',
            16 => 'Recipients count is more than 60',
            17 => 'Array lengths are not equal',
        ];
        $client = $this->client->SendMultiSMS(
            $fromNum,
            $toNum,
            $content,
            $is_flash,
            $this->username,
            $this->password
        );
        $code = isset($client[0])?(int) $client[0]:-1;
        $result = [
            'status' => false,
            'message' => 'Error while processing request!',
            'code' => null,
        ];
        if (in_array($code, [0, 4, 5, 6, 16, 17, -1])) {
            $result = [
                'message' => $states[$code],
                'code' => $code,
            ];
        } else {
            foreach($client as $index => $c) {
                if (in_array($c, [1, 2, 7, 9, 10, 11])) {
                    $codes[] = [$toNum[$index] => ['status' => false, 'message' => $states[$c]]];
                } else {
                    $codes[] = [$toNum[$index] => ['status' => true, 'message' => $c]];
                }
            }
            $result = [
                'status' => true,
                'message' => 'Request process was successful',
                'code' => $codes,
            ];
        }

        return $result;
    }

    /**
     * Get message status by it`s ID.
     * An array with message ID in each row
     * If you get TRUE in "status", you will have an array each row has a  based on SMS Panel Documentation
     * If you get FALSE in "status", the "message" and "code" are clear and based on SMS Panel Documentation
     *
     * @return array
     * [
     *    'status' => Processing of request is successful or not
     *    'message' => A clear message based on SMS Panel Documentation
     *    'code' => If STATUS is true, contains a float number which is panel credit, else you have a code returns by API, and it represents the "message" by code provided in SMS Panel Documentation
     * ]
     */
    public function getStatus(array $ids) {
        $states = [
            0 => 'Sent to TC',
            1 => 'Delivered to recipient',
            2 => 'Not delivered to recipient',
            3 => 'Delivered to TC',
            4 => 'Not delivered to TC',
            5 => 'Ready to send',
            6 => 'Bounced',
            14 => 'Status code is not valid',
            15 => 'Invalid username or password',
            16 => 'ID`s array count is more than 50',
            17 => 'User is inactive',
            18 => 'Panel is expired',
        ];
        $client = $this->client->GetStatus(
            $this->username,
            $this->password,
            $ids
        );
        $code = isset($client[0])?(int) $client[0]:-1;
        $result = [
            'status' => false,
            'message' => 'Error while processing request!',
            'code' => $code,
        ];
        $codes = [];
        $status = false;
        foreach ($client as $index => $c) {
            switch ($c) {
                case 0:
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 14: {
                    $status = true;
                    $codes[] = [$ids[$index] => ['code' => (int) $c, 'state' => $states[$c]]];
                    break;
                }
                case 15:
                case 16:
                case 17:
                case 18: {
                    $result['message'] = $states[$c];
                    break;
                }
                case -1:
                default: {
                    $result['message'] = 'Unidentified answer from API';
                }
            }
        }
        if ($status) {
            $result = [
                'status' => true,
                'message' => 'Request process was successful',
                'code' => $codes,
            ];
        }
        return $result;
    }

    /**
     * Get user credit.
     * No parameters are needed. The result array is clear! If you get TRUE in "status" it means "code" has a float number which is user credit
     * If you get FALSE in "status", the "message" and "code" are clear and based on SMS Panel Documentation
     *
     * @return array
     * [
     *    'status' => Processing of request is successful or not
     *    'message' => A clear message based on SMS Panel Documentation
     *    'code' => If STATUS is true, contains a float number which is panel credit, else you have a code returns by API, and it represents the "message" by code provided in SMS Panel Documentation
     * ]
     */
    public function getCredit() {
        $states = [
            -1 => 'Unidentified answer from AP',
            0 => 'Invalid username or password',
            5 => 'User is inactive',
            6 => 'Panel is expired',
        ];
        $client = $this->client->GetCredit(
            $this->username,
            $this->password,
        );
        $code = isset($client[0])?(is_string($client[0])?(int) $client[0]:null):-1;
        $result = [
            'status' => !in_array($code, [-1, 0, 5, 6]),
            'message' => $states[$code],
            'code' => in_array($code, [-1, 0, 5, 6])?(float) $client[0]:$code,
        ];
        return $result;
    }
}

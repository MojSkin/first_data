<?php

namespace App\Http\Controllers;

use App\Helpers\SMSHelper;
use Illuminate\Http\Request;

class PanelController extends Controller
{
    private $client;
    public function __construct()
    {
        $this->client = new SMSHelper();
    }

    public function index()
    {
        return view('welcome');
    }

    public function sendMessage(Request $request)
    {
        $recipients = explode(',', $request->recipients);
        $senders = explode(',', $request->senders);
        $content = is_null($request['content'])?'':$request['content'];
        $is_flash = $request->is_flash?1:0;
        if (count($senders)===1) {
            $messageStatus = $this->client->sendSMS($senders[0], $recipients, $content, $is_flash);
        } else {
            $contents = [];
            $flashes = [];
            foreach ($recipients as $index => $r) {
                $contents[] = $content.' '.$index;
                $flashes[] = $is_flash;
            }
            $messageStatus = $this->client->sendMultiSMS($senders, $recipients, $contents, $flashes);
        }
        return view('welcome')->with(['messageStatus' => $messageStatus]);
    }

    public function getCredit(Request $request)
    {
        $credit = $this->client->getCredit();
        return view('welcome')->with(['credit' => $credit]);
    }

    public function getStatus(Request $request)
    {
        $status = $this->client->getStatus(explode(',', $request->ids));
        return view('welcome')->with(['status' => $status]);
    }
}

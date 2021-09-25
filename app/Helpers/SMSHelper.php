<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Hash;

class SMSHelper
{
    private $username;
    private $password;
    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = Hash::
    }
}

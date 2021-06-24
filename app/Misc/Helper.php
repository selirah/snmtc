<?php

namespace App\Misc;


class Helper
{
    public static function generateRandomString($length = 10)
    {
        $string = array_merge(range('a', 'z'), range('A', 'Z'));
        shuffle($string);
        return substr(implode($string), 0, $length);
    }

    public static function sendSMS($phone, $message, $sender)
    {
        $url = 'http://sms.ebitsgh.com/smsapi';
        $key = 'DnVYnGb276yeMsyooGZata2SR';
        $message = urlencode($message);
        $send = $url . '?key=' . $key . '&to=' . $phone . '&msg=' . $message . '&sender_id=' . $sender;
        return file_get_contents($send);
    }

    public static function generateRandomToken()
    {
        try {
            $bytes = random_bytes(100);
            $randomPassword = strtolower(bin2hex($bytes));
            return $randomPassword;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public static function sanitizePhone($phone)
    {
        $phone = str_replace(" ", "", $phone);
        $phone = str_replace("-", "", $phone);
        $phone = str_replace("+", "", $phone);
        filter_var($phone, FILTER_SANITIZE_NUMBER_INT);

        if ((substr($phone, 0, 1) == 0) && (strlen($phone) == 10)) {
            return substr_replace($phone, "233", 0, 1);
        } elseif ((substr($phone, 0, 1) != 0) && (strlen($phone) == 9)) {
            return "233" . $phone;
        } elseif ((substr($phone, 0, 3) == "233") && (strlen($phone) == 12)) {
            return $phone;
        } elseif ((substr($phone, 0, 5) == "00233") && (strlen($phone) == 14)) { //if number begin with 233 and length is 12
            return substr_replace($phone, "233", 0, 5);
        } else {
            return $phone;
        }
    }
}

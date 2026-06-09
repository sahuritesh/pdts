<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Mail;
class Email_Model extends Model
{
    public static function send_email($toaddresss,$subject,$view,$data,$cc=null,$attach=null){
        $sendermail = env('MAIL_USERNAME');
        $fromname = env('MAIL_FROM_NAME');
        try{
            Mail::send($view,$data,function($message) use ($toaddresss,$subject,$sendermail,$cc,$attach,$fromname) {
                $recipients = is_array($toaddresss) ? $toaddresss : explode(',', $toaddresss);
                $message->to($recipients)->subject($subject);
                $message->from($sendermail,$fromname);
                if (!empty($cc)) {
                    $ccRecipients = is_array($cc) ? $cc : explode(',', $cc);
                    $message->cc($ccRecipients);
                }
                if(isset($attach) && !empty($attach)){
                    // Handle attachment - can be string (file path) or array with options
                    if (is_array($attach)) {
                        // Array format: ['path' => $path, 'as' => $name, 'mime' => $mime]
                        $path = $attach['path'] ?? $attach[0] ?? null;
                        $name = $attach['as'] ?? $attach['name'] ?? null;
                        $mime = $attach['mime'] ?? null;
                        
                        if ($path && file_exists($path)) {
                            if ($name && $mime) {
                                $message->attach($path, ['as' => $name, 'mime' => $mime]);
                            } elseif ($name) {
                                $message->attach($path, ['as' => $name]);
                            } else {
                                $message->attach($path);
                            }
                        }
                    } elseif (is_string($attach) && file_exists($attach)) {
                        // Simple file path string
                        $message->attach($attach);
                    }
                }
            });
            return true;
        } catch(Exception $e){
              if($e){
                 //return false;
              }
        }
    }
}
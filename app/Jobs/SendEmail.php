<?php

namespace App\Jobs;

use App\Http\Controllers\MailController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationEmailWithoutfile;
use App\Models\User;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $title;
    protected $body;
    protected $mode ;
    protected $reciever_id;

    public function __construct($reciever_id,$title,$body,$mode=0)
    {
        $this->title = $title;
        $this->body = $body;
        $this->mode = $mode;
        $this->reciever_id = $reciever_id;
    }


    public function handle(): void
    {
        (new MailController())->sendNotification($this->reciever_id,$this->title,$this->body,$this->mode);
    }
}

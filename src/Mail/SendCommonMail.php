<?php

namespace Hnllyrp\LaravelSupport\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendCommonMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @var
     */
    public $mailContent;

    /**
     * Create a new message instance.
     *
     * @param  $mailContent
     * @return void
     */
    public function __construct($mailContent)
    {
        $this->mailContent = $mailContent;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        /**
         * 发送通用邮件
         */
        $template_subject = $this->mailContent['template_subject'] ?? '';
        $template_content = $this->mailContent['template_content'] ?? '';
        $is_html = $this->mailContent['is_html'] ?? 0;

        return $this->view('support::emails.common_views')->subject($template_subject)->with([
            'content' => $template_content,
            'is_html' => $is_html,
        ]);
    }
}

<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\Mail;

use Arikaim\Core\Mail\Interfaces\MailInterface;
use Arikaim\Core\Interfaces\MailerInterface;

/**
 * Mail base class
 */
class Mail implements MailInterface
{ 
    /**
     * Message
     *
     * @var Swift_Message
     */
    protected $message;

    /**
     * Mailer
     *
     * @var MailerInterface
     */
    private $mailer;

    /**
     * Constructor
     *
     * @param MailerInterface $mailer
     */
    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
        $this->message = new \Swift_Message();
        $this->setDefaultFrom();
    } 

    /**
     * Set default from field
     *    
     * @return Mail
     */
    public function setDefaultFrom()
    {
        $options = $this->mailer->getOptions();
        $from = $options['mailer']['from']['email'] ?? null;
        $fromName = $options['mailer']['from']['name'] ?? null;
        if (empty($from) == false) {
            $this->from($from,$fromName);
        }

        return $this;
    }

    /**
     * Create mail
     *
     * @param MailerInterface $mailer
     * @return MailInterface
     */
    public static function create(MailerInterface $mailer)
    {
        return new Self($mailer);
    }

    /**
     * Build email
     *
     * @return Mail
     */
    public function build()
    {
        return $this;
    }
  
    /**
     * Set email subject
     *
     * @param string $subject
     * @return Mail
     */
    public function subject(string $subject)
    {
        $this->message->setSubject($subject);

        return $this;
    }

    /**
     * Attach file
     *
     * @param string $file
     * @return Mail
     */
    public function attach(string $file)
    {
        $attachment = \Swift_Attachment::fromPath($file);
        $this->message->attach($attachment);

        return $this;
    }

    /**
     * Set from
     *
     * @param string|array $email
     * @param string|null $name
     * @return Mail
     */
    public function from($email, ?string $name = null)
    {
        $this->message->setFrom($email,$name);

        return $this;
    } 

    /**
     * Set to
     *
     * @param string|array $email
     * @param string|null $name
     * @return Mail
     */
    public function to($email, ?string $name = null)
    {        
        $this->message->setTo($email,$name);   

        return $this;
    }

    /**
     * Set reply to
     *
     * @param string|array $email
     * @param string|null $name
     * @return Mail
     */
    public function replyTo($email, ?string $name = null)
    {
        $this->message->setReplyTo($email,$name);

        return $this;
    }

    /**
     * Set cc
     *
     * @param string|array $email
     * @param string|null $name
     * @return Mail
     */
    public function cc($email, ?string $name = null)
    {
        $this->message->setCc($email,$name);

        return $this;
    }

    /**
     * Set bcc
     *
     * @param string|array $email
     * @param string|null $name
     * @return Mail
     */
    public function bcc($email, ?string $name = null)
    {
        $this->message->setBcc($email,$name);

        return $this;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     * @return Mail
     */
    public function priority(int $priority = 3)
    {
        $this->message->setPriority($priority);

        return $this;
    }
    
    /**
     * Set email body
     *
     * @param string $message
     * @return Mail
     */
    public function message(string $message)
    {
        $this->message->setBody($message);

        return $this;
    }

    /**
     * Set email content type
     *
     * @param string $type
     * @return Mail
     */
    public function contentType(string $type = 'text/plain')
    {
        $this->message->setContentType($type);

        return $this;
    }

    /**
     * Return message body
     *
     * @return string
     */
    public function getBody(): ?string
    {
        return $this->message->getBody();
    }

    /**
     * Get message instance
     *
     * @return Swift_Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Send email
     *
     * @return bool
     */
    public function send(): bool 
    {
        return $this->mailer->send($this);
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getError(): ?string
    {
        return $this->mailer->getErrorMessage();
    }
}

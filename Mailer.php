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

use Arikaim\Core\Mail\Mail;
use Arikaim\Core\Utils\Utils;

use Arikaim\Core\Mail\Interfaces\MailInterface;
use Arikaim\Core\Interfaces\MailerInterface;
use Arikaim\Core\Interfaces\View\HtmlPageInterface;

/**
 * Send emails
 */
class Mailer implements MailerInterface
{
    /**
     * Mailer object
     *
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * Mailer error message
     *
     * @var string
     */
    private $error;

    /**
     * Options
     *
     * @var array
     */
    private $options;
 
    /**
    * Constructor
    *
    * @param array $options
    * @param HtmlPageInterface $page
    */
    public function __construct(array $options, HtmlPageInterface $page = null) 
    {
        $this->error = null;
        $this->options = $options;
        $this->page = $page;

        $transport = $this->createTransportDriver();
    
        $this->mailer = new \Swift_Mailer($transport);
    }

    /**
     * Return options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get email compillers config
     *
     * @return array
     */
    public function getCompilers()
    {
        $compilers = $this->options['mailer']['email']['compillers'] ?? [];
        if (\is_string($compilers) == true) {
            $compilers = \json_encode($compilers);
        }

        return (\is_array($compilers) == false) ? [] : $compilers;
    }

    /**
     * Create message
     *
     * @param string|null $componentName
     * @param array $params
     * @param string|null language
     * @return MailInterface
     */
    public function create($componentName = null, $params = [], $language = null)
    {
        $mail = new Mail($this);

        return (empty($componentName) == false) ? $this->loadEmailComponent($componentName,$params,$language) : $mail;        
    }

    /**
     * Load email component adn return mail object
     *
     * @param string $componentName
     * @param array $params
     * @param string|null $language
     * @return MailInterface
     */
    public function loadEmailComponent($componentName, $params = [], $language = null)
    {
        $emailComponent = $this->page->createEmailComponent($componentName,$params,$language);
        $emailComponent->setEmailCompillers($this->getCompilers());

        $component = $emailComponent->renderComponent();
        $properties = $component->getProperties();
        $body = $component->getHtmlCode();

        $mail = new Mail($this);
        $mail->message($body);

        if (Utils::hasHtml($body) == true) {
            $mail->contentType('text/html');
        } else {
            $mail->contentType('text/plain');
        }
        
        // subject
        $subject = $properties['subject'] ?? '';
        if (empty($subject) == false) {
            $mail->subject($subject);
        }

        return $mail;
    }

    /**
     * Create transport driver
     *
     * @return \Swift_Transport
     */
    private function createTransportDriver()
    {
        if ($this->isSendmailTransport() === true) {
            return new \Swift_SendmailTransport('/usr/sbin/sendmail -bs');
        }    

        $transport = new \Swift_SmtpTransport($this->getSmtpHost(),$this->getSmtpPort());
        $transport->setUsername($this->getUserName());
        $transport->setPassword($this->getPassword());   
        
        if ($this->getSmtpSsl() == true) {
            $transport->setEncryption('ssl');    
        }              
       
        return $transport;
    }

    /**
     * Get smtp ssl
     *
     * @return string|false
     */
    public function getSmtpSsl()
    {
        return $this->options['mailer']['smpt']['ssl'] ?? false;
    }


    /**
     * Get smtp host
     *
     * @return string|null
     */
    public function getSmtpHost()
    {
        return $this->options['mailer']['smpt']['host'] ?? null;
    }

    /**
     * Get smtp port
     *
     * @return string|null
     */
    public function getSmtpPort()
    {
        return $this->options['mailer']['smpt']['port'] ?? null;
    }

    /**
     * Get smtp username
     *
     * @return string|null
     */
    public function getUserName()
    {
        return $this->options['mailer']['username'] ?? null;
    }

    /**
     * Get smtp password
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->options['mailer']['password'] ?? null;
    }

    /**
     * Return true if transport is sendmail
     *
     * @return boolean
     */
    public function isSendmailTransport()
    {
        return (bool)$this->options['mailer']['use']['sendmail'] ?? false;
    } 

    /**
     * Send email
     *
     * @param MailInterface $message
     * @return bool
     */
    public function send($message)
    {
        $this->error = null;

        $message->build();
        $mail = $message->getMessage();

        try {
            $result = $this->mailer->send($mail);
        } catch (\Exception $e) {
            //throw $th;
            $this->error = $e->getMessage();
            $result = false;
        }
        
        return ($result > 0);
    }

    /**
     * Get mailer transport
     *
     * @return \Swift_Transport
     */
    public function getTransport()
    {
        return $this->mailer->getTransport();
    }

    /**
     * Set transport driver
     *
     * @param \Swift_Transport $driver
     * @return Swift_Mailer
     */
    public function setTransport($driver)
    {
        return $this->mailer = new \Swift_Mailer($driver);
    }

    /**
     * Get error message
     *
     * @return string|null
     */
    public function getErrorMessage()
    {
        return $this->error;
    }    
}
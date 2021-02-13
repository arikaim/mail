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
use Arikaim\Core\Mail\Interfaces\MailerDriverInterface;

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
     * @var string|null
     */
    private $error = null;

    /**
     * Options
     *
     * @var array
     */
    private $options = [];
 
    /**
     * Page html component
     *
     * @var HtmlPageInterface|null
     */
    private $page;

    /**
    * Constructor
    *
    * @param array $options
    * @param HtmlPageInterface $page
    */
    public function __construct(
        array $options, 
        ?HtmlPageInterface $page = null, 
        ?MailerDriverInterface $driver = null
    ) 
    {
        $this->error = null;
        $this->options = $options;
        $this->page = $page;

        $transport = (empty($driver) == true) ? Self::crateSendmailTranspart() : $driver->getMailerTransport();
    
        $this->mailer = new \Swift_Mailer($transport);
    }

    /**
     * Create sendmail transport
     *
     * @return Swift_Transport
     */
    public static function crateSendmailTranspart()
    {
        return new \Swift_SendmailTransport('/usr/sbin/sendmail -bs');
    }

    /**
     * Return options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get email compillers config
     *
     * @return array
     */
    public function getCompilers(): array
    {
        $compilers = $this->options['compillers'] ?? [];
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
    public function create(?string $componentName = null, array $params = [], ?string $language = null)
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
    public function loadEmailComponent(string $componentName, array $params = [], ?string $language = null)
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
     * Get from email option
     *
     * @return string
     */
    public function getFromEmail(): string
    {
        return $this->options['from_email'] ?? '';
    } 

    /**
     * Get from name option
     *
     * @return string
     */
    public function getFromName(): string
    {
        return $this->options['from_name'] ?? '';
    }
   
    /**
     * Send email
     *
     * @param MailInterface $message
     * @return bool
     */
    public function send($message): bool
    {
        $this->error = null;

        $message->build();
        $mail = $message->getMessage();

        if (empty($mail->getFrom()) == true) {
            $mail->setFrom($this->getFromEmail(),$this->getFromName());
        }

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
    public function getErrorMessage(): ?string
    {
        return $this->error;
    }    
}
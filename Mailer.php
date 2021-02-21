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
use Arikaim\Core\Interfaces\LoggerInterface;

use Arikaim\Core\Logger\Traits\LoggerTrait;

/**
 * Send emails
 */
class Mailer implements MailerInterface
{
    use LoggerTrait;

    const LOG_ERROR_MESSAGE = 'Error send email';
    const LOG_INFO_MESSAGE  = 'Email send successful.';

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
     * Driver name
     *
     * @var string|null
     */
    private $driverName = null;

    /**
    * Constructor
    *
    * @param array $options
    * @param HtmlPageInterface $page
    */
    public function __construct(
        array $options, 
        ?HtmlPageInterface $page = null, 
        ?MailerDriverInterface $driver = null,
        ?LoggerInterface $logger = null
    ) 
    {
        $this->error = null;
        $this->options = $options;
        $this->page = $page;
        $this->setLogger($logger);

        if (empty($driver) == true) {
            $transport = Self::crateSendmailTranspart();
            $this->driverName = 'sendmail';
        } else {
            $transport = $driver->getMailerTransport();
            $this->driverName = $driver->getDriverName();
        }   
      
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
     * Get option value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
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
            
        } catch (\Swift_TransportException $e) { 
            $this->error = $e->getMessage();
            $result = false;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $result = false;
        }
        
        if ($result > 0) {
            if ($this->getOption('log',false) == true) {  
                $this->logInfo(Self::LOG_INFO_MESSAGE,['driver' => $this->driverName]);
            }
            return true;
        } 

        if ($this->getOption('log_error',false) == true) {
            $this->logError(Self::LOG_ERROR_MESSAGE,[
                'error'  => $this->error,
                'driver' => $this->driverName
            ]);
        }

        return false;       
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
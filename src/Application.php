<?php
/*
 *  Copyright notice
 *
 *  (c) 2016 Andreas Thurnheer-Meier <tma@iresults.li>, iresults
 *  Daniel Corn <cod@iresults.li>, iresults
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * @author COD
 * Created 07.07.16 16:22
 */


namespace Iresults\Cift;


use Swift_Mailer;
use Swift_Message;
use Swift_SendmailTransport;
use Swift_Transport;

class Application
{
    /**
     * @var string
     */
    private $scriptPath;

    /**
     * @param array $arguments
     * @return int
     */
    public function run(array $arguments): int
    {
        if (count($arguments) < 4) {
            $this->printUsage(isset($arguments[0]) ? $arguments[0] : '');

            return 1;
        }
        list($sender, $subject, $body, $recipients) = $this->parseArguments($arguments);
        $this->printInfo($recipients, $sender, $subject, $body);

        $this->prepareEnvironment();
        $success = $this->sendEmail($recipients, $sender, $subject, $body);

        return $success ? 0 : 1;
    }

    /**
     * @param array  $recipients
     * @param string $sender
     * @param string $subject
     * @param string $body
     * @return bool
     */
    private function sendEmail(array $recipients, string $sender, string $subject, string $body): bool
    {
        $contentType = $this->getSendAsHtml($body) ? 'text/html' : null;
        $message = (new Swift_Message())
            ->setSubject($subject)
            ->setFrom(array($sender => $sender))
            ->setTo($recipients)
            ->setBody($body, $contentType);

        if (!$this->getMailer()->send($message, $failures)) {
            $this->printFailures($failures);

            return false;
        }

        $this->println('Success! No errors');

        return true;
    }

    /**
     * @return Swift_Mailer
     */
    private function getMailer()
    {
        return Swift_Mailer::newInstance($this->getTransport());
    }

    /**
     *
     */
    private function prepareEnvironment()
    {
        if (function_exists('mb_internal_encoding') && ((int)ini_get('mbstring.func_overload')) & 2) {
            mb_internal_encoding('ASCII');
        }
    }

    /**
     * @param string[] $recipients
     * @param string   $sender
     * @param string   $subject
     * @param string   $body
     */
    private function printInfo(array $recipients, string $sender, string $subject, string $body)
    {
        $this->println('Send email "%s"', $subject);
        $this->println('to          %s', implode(', ', $recipients));
        $this->println('from        %s', $sender);
        $this->println(
            'with body length %d%s',
            strlen($body),
            $this->getSendAsHtml($body) ? ' sent as HTML' : ''
        );
    }

    /**
     * @param string $format
     * @param array  ...$variables
     * @return Application
     */
    private function println(string $format, ...$variables): self
    {
        $format .= PHP_EOL;
        vprintf($format, $variables);

        return $this;
    }

    /**
     * @param string $pathToScript
     */
    private function printUsage(string $pathToScript)
    {
        $this->println('Usage: %s recipient sender subject [body]', $pathToScript ?: 'bin/cift');
    }

    /**
     * @return string
     */
    private function readBodyFromStdIn():string
    {
        $body = $this->readBodyFromPipedStdIn();
        if (!$body) {
            return $this->readBodyFromTerminal();
        }

        return $body;
    }

    private function readBodyFromTerminal()
    {
        $this->println('Please type in the message (submit with ctrl-d)');
        $body = '';
        while (false !== ($line = fgets(STDIN))) {
            $body .= $line;
        }

        return $body;
    }

    /**
     * @return string
     */
    private function readBodyFromPipedStdIn()
    {
        stream_set_blocking(STDIN, false);
        $body = '';
        while (false !== ($line = fgets(STDIN))) {
            $body .= $line;
        }

        stream_set_blocking(STDIN, true);

        return $body;

    }

    /**
     * @param string $recipientData
     * @return array
     */
    private function prepareRecipients(string $recipientData): array
    {
        return array_filter(explode(',', $recipientData));
    }

    /**
     * @return Swift_Transport
     */
    private function getTransport()
    {
        //$transport = Swift_SmtpTransport::newInstance('smtp.example.org', 25)
        //    ->setUsername('your username')
        //    ->setPassword('your password');

        return Swift_SendmailTransport::newInstance();
    }

    /**
     * @param array $failures
     */
    private function printFailures(array $failures)
    {
        if (count($failures) > 1) {
            $this->println('%d failures occurred', count($failures));
        } else {
            $this->println('A failure occurred');
        }
        foreach ($failures as $failure) {
            $this->println('- %s', $failure);
        }
    }

    /**
     * @param string $body
     * @return bool
     */
    private function getSendAsHtml(string $body):bool
    {
        return strpos($body, '<') !== false;
    }

    /**
     * @param array $arguments
     * @return array
     */
    private function parseArguments(array $arguments): array
    {
        if (count($arguments) >= 5) {
            list($this->scriptPath, $recipientData, $sender, $subject, $body) = $arguments;
        } else {
            list($this->scriptPath, $recipientData, $sender, $subject,) = $arguments;
            $body = $this->readBodyFromStdIn();
        }

        $recipients = $this->prepareRecipients($recipientData);

        return array($sender, $subject, $body, $recipients);
    }
}

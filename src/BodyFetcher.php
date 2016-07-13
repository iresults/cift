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
 * Created 13.07.16 12:02
 */


namespace Iresults\Cift;


class BodyFetcher
{
    /**
     * @param string $body
     * @return string
     */
    public function fetch(string $body):string
    {
        if (file_exists($body)) {
            return $this->fetchFile($body);
        }

        return $this->fetchUrl($body);
    }

    /**
     * @param string $filePath
     * @return string
     */
    private function fetchFile(string $filePath): string
    {
        if (!is_readable($filePath)) {
            throw new \RuntimeException(sprintf('File "%s" not readable'), $filePath);
        }

        return (string)file_get_contents($filePath);
    }

    /**
     * @return string
     */
    public function fetchFromStdIn():string
    {
        $body = $this->readBodyFromPipedStdIn();
        if (!$body) {
            return $this->readBodyFromTerminal();
        }

        return $body;
    }

    /**
     * @param string $url
     * @return string
     */
    private function fetchUrl(string $url): string
    {
        if (!ini_get('allow_url_fopen')) {
            throw new \RuntimeException('allow_url_fopen must be enabled');
        }

        $context = stream_context_create(
            [
                'http' => [
                    'method'  => 'GET',
                    'timeout' => 5,
                ],
            ]
        );

        $content = file_get_contents($url, false, $context);
        if ($content === false) {
            throw new \RuntimeException(sprintf('Could not download content from "%s"', $url));
        }

        return (string)$content;
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
     * @param string $format
     * @param array  ...$variables
     * @return BodyFetcher
     */
    private function println(string $format, ...$variables): self
    {
        $format .= PHP_EOL;
        vprintf($format, $variables);

        return $this;
    }
}
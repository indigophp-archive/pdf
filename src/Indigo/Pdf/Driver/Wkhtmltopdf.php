<?php

namespace Indigo\Pdf\Driver;

use Indigo\Pdf\Driver;
use Symfony\Component\Process\Process;

class Wkhtmltopdf extends Driver
{
    // Regular expression to detect HTML strings
    const REGEX_HTML = '/<html.*>/';

    /**
     * @var array pdf driver config defaults
     */
    protected $defaults = array(
        'bin'      => '/usr/bin/wkhtmltopdf',
        'tmp'      => '/tmp',
        'escape'   => true,
        'version9' => false, //?
        'options'  => array(
            'orientation' => 'Portrait',
            'page-size'   => 'A4',
            'encoding'    => 'UTF-8',
        ),
        'page_options' => array(),
    );

    /**
     * @var array temporary file names
     */
    protected $tmpFiles = array();

    /**
     * @var array pages of PDF
     */
    protected $pages = array();

    /**
     * Remove temporary PDF file and pages when script completes
     */
    public function __destruct()
    {
        foreach ($this->tmpFiles as $file) {
            unlink($file);
        }
    }

    public function output($file = null)
    {
        $tmpFile = $this->render();

        if ($tmpFile) {
            $file = $file ?: basename($tmpPath);

            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-Type: application/pdf');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($tmpFile));
            header("Content-Disposition: inline; filename=\"$file\"");

            readfile($tmpFile);
            return true;
        }

        return false;
    }

    public function download($file = null)
    {
        $this->output($file);

        header("Content-Disposition: attachment; filename=\"$file\"");
    }

    public function save($file)
    {
        $tmpFile = $this->render();

        if ($tmpFile) {
            copy($tmpFile, $file);
            return true;
        }

        return false;
    }

    public function raw()
    {
        return file_get_contents($this->getPdf());
    }

    public function addPage($input, array $options = array())
    {
        $options = array_merge($this->page_options, $options);
        $options['input'] = $this->isHtml($input) ? $this->createTmpFile($input) : $input;
        $this->pages[] = $options;

        return $this;
    }

    public function addToc(array $options = array())
    {
        $options = array_merge($this->page_options, $options);
        // $options['input'] = ($this->version9 ? '--' : '')."toc";
        $options['input'] = "toc";
        $this->pages[] = $options;
    }

    public function write($input)
    {
        $page = end($this->pages);

        if (in_array($page['input'], $this->tmpFiles)) {
            $content = file_get_contents($page['input']);
            $content .= $this->isHtml($input) ? $input : file_get_contents($input);
            file_put_contents($page['input'], $content);
            array_pop($this->pages);
            $this->pages[] = $page;
        } else {
            return $this->addPage($input);
        }
        return $this;
    }

    /**
     * Create a tmp file, optionally with given content
     *
     * @param  string|null $content the file content
     * @return string               the path to the created file
     */
    protected function createTmpFile($content = null)
    {
        $tmpPath = $this->getConfig('tmp', sys_get_temp_dir());
        $tmpFile = tempnam($tmpPath,'tmp_WkHtmlToPdf_');

        if ( ! is_null($content)) {
            rename($tmpFile, ($tmpFile .= '.html'));
            file_put_contents($tmpFile, $content);
        }

        $this->tmpFiles[] = $tmpFile;

        return $tmpFile;
    }

    protected function escapeCommand($command)
    {
        if ($this->getConfig('escape', true)) {
            $command = escapeshellarg($command);
        }

        return $command;
    }

    protected function buildOptions($options)
    {
        $output = '';

        foreach ($options as $key => $value) {
            if (is_numeric($key)) {
               $output .= " --$value";
            } else {
                $output .= " --$key " . $this->escapeCommand($value);
            }
        }

        return $output;
    }

    public function buildCommand($file)
    {
        $command = $this->escapeCommand($this->getConfig('bin', '/usr/bin/wkhtmltopdf'));
        $command .= ' ' . $this->buildOptions($this->options);

        foreach ($this->pages as $page) {
            $command .= ' ' . $page['input'];
            unset($page['input']);
            $command .= $this->buildOptions($page);
        }

        return $command . ' ' . $file;
    }

    public function render()
    {
        $tmpFile = $this->createTmpFile();
        $command = $this->buildCommand($tmpFile);

        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();

        if ( ! $process->isSuccessful()) {
            if ( ! file_exists($tmpFile) or filesize($tmpFile) === 0) {
                throw new \RuntimeException('Could not run command:' . $process->getErrorOutput());
            } else {
                throw new \Exception('Error occured while creating PDF.');
            }
        }

        return $process->isSuccessful() ? $this->tmpFile = $tmpFile : false;
    }

    protected function getPdf()
    {
        return $this->tmpFile ?: $this->render();
    }
}

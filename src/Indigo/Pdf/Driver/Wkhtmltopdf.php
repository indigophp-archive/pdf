<?php

namespace Indigo\Pdf\Driver;

use Indigo\Pdf\Driver;

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
        
    }

    public function addPage($input, array $options = array())
    {
        $options = array_merge($this->page_options, $options);
        $options['input'] = $this->isHtml($input) ? $this->createTmpFile($input) : $input;
        $this->pages[] = $options;

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
        // $tmpPath = $this->getConfig('tmp', sys_get_temp_dir());
        $tmpPath = sys_get_temp_dir();
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
        if (true) {
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
        $command = $this->escapeCommand('/usr/bin/wkhtmltopdf');
        $command .= ' ' . $this->buildOptions($this->options);

        foreach ($this->pages as $page) {
            $command .= ' ' . $page['input'];
            unset($page['input']);
            $command .= $this->buildOptions($page);
        }

        return $command . ' ' . $file;
    }

    public function render($force = false)
    {
        if ( ! empty($this->tmpFile) and $force === false) {
            return $this->tmpFile;
        }

        $tmpFile = $this->createTmpFile();
        $command = $this->buildCommand($tmpFile);

        $descriptors = array(
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        );
        $result = null;

        $process = proc_open($command, $descriptors, $pipes, null, null, array('bypass_shell'=>true));

        if (is_resource($process)) {
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $result = proc_close($process);
        }

        return $result === 0 ? $this->tmpFile = $tmpFile : false;
    }
}

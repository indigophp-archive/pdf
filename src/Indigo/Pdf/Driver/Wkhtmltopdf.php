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
    );

    protected $mapOptions = array(
        'orientation' => array(
            array('P', 'L'),
            array('Portrait', 'Landscape'),
        ),
    );

    private $validOptions = array(
        'dpi',
        'grayscale',
        'image-dpi',
        'image-quality',
        'lowquality',
        'margin-bottom',
        'margin-left',
        'margin-right',
        'margin-top',
        'orientation',
        'output-format',
        'page-height',
        'page-size',
        'page-width',
        'no-pdf-compression',
        'title',
    );

    private $validPageOptions = array(
        'footer-center',
        'footer-font-name',
        'footer-font-size',
        'footer-html',
        'footer-left',
        'footer-line',
        'no-footer-line',
        'footer-right',
        'footer-spacing',
        'header-center',
        'header-font-name',
        'header-font-size',
        'header-html',
        'header-left',
        'header-line',
        'no-header-line',
        'header-right',
        'header-spacing',
        'replace',
        'allow',
        'background',
        'no-background',
        'checkbox-checked-svg',
        'checkbox-svg',
        'cookie',
        'custom-header',
        'custom-header-propagation',
        'no-custom-header-propagation',
        'debug-javascript',
        'no-debug-javascript',
        'default-header',
        'encoding',
        'disable-external-links',
        'enable-external-links',
        'disable-forms',
        'enable-forms',
        'images',
        'no-images',
        'disable-internal-links',
        'enable-internal-links',
        'disable-javascript',
        'enable-javascript',
        'javascript-delay',
        'load-error-handling',
        'disable-local-file-access',
        'enable-local-file-access',
        'minimum-font-size',
        'exclude-from-outline',
        'include-in-outline',
        'page-offset',
        'password',
        'disable-plugins',
        'enable-plugins',
        'post',
        'post-file',
        'print-media-type',
        'no-print-media-type',
        'proxy',
        'radiobutton-checked-svg',
        'radiobutton-svg',
        'run-script',
        'disable-smart-shrinking',
        'enable-smart-shrinking',
        'stop-slow-scripts',
        'no-stop-slow-scripts',
        'disable-toc-back-links',
        'enable-toc-back-links',
        'user-style-sheet',
        'username',
        'window-status',
        'zoom',
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
        $options = array_merge($this->pageOptions, $options);
        $options['input'] = $this->isHtml($input) ? $this->createTmpFile($input) : $input;
        $this->pages[] = $options;

        return $this;
    }

    public function addToc(array $options = array())
    {
        $options = array_merge($this->pageOptions, $options);
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

    /**
     * Build command string
     * @param  string $file filename
     * @return string       command string
     */
    public function buildCommand($file)
    {
        $command = $this->escapeCommand($this->getConfig('bin', '/usr/bin/wkhtmltopdf'));
        $command .= $this->buildOptions($this->options, $this->validOptions);
        $command .= $this->buildOptions($this->pageOptions, $this->validPageOptions);

        foreach ($this->pages as $page) {
            $command .= ' ' . $page['input'];
            unset($page['input']);
            $command .= $this->buildOptions($page, $this->validPageOptions);
        }

        return $command . ' ' . $file;
    }

    /**
     * Build command line options from array
     * @param  array  $options      Input parameters
     * @param  array  $validOptions Valid parameter names
     * @return string               argument string
     */
    protected function buildOptions(array $options = array(), array $validOptions = array())
    {
        $output = '';
        foreach ($options as $key => $value) {
            // Only include valid options
            $option = is_numeric($key) ? $value : $key;
            if ( ! in_array($option, $validOptions) and ! empty($validOptions)) {
                continue;
            }

            // Is it an option or option-value pair(s)
            if (is_numeric($key)) {
               $output .= " --$value";
            } elseif(is_array($value)) {
                foreach ($value as $index => $option) {
                    // Is it an option value or a pair of values
                    if (is_string($index)) {
                        $output .= " --$key " . $this->escapeCommand($index) . ' ' . $this->escapeCommand($option);
                    } else {
                        $output .= " --$key " . $this->escapeCommand($option);
                    }
                }
            } else {
                $output .= " --$key " . $this->escapeCommand($value);
            }
        }

        return $output;
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

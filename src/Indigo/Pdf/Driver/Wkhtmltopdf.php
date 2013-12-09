<?php

namespace Indigo\Pdf\Driver;

use Indigo\Pdf\Driver;
use Symfony\Component\Process\ProcessBuilder;

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
     * @var string temporary PDF file
     */
    protected $tmpFile;

    /**
     * Remove temporary PDF file and pages when script completes
     */
    public function __destruct()
    {
        foreach ($this->tmpFiles as $file) {
            unlink($file);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setTitle($title)
    {
        $this->options['title'] = $title;
    }

    /**
     * {@inheritdoc}
     */
    public function addPage($input, array $options = array())
    {
        // $options = array_merge($this->pageOptions, $options);
        $options['input'] = $this->isHtml($input) ? $this->createTmpFile($input) : $input;
        $this->pages[] = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addToc(array $options = array())
    {
        // $options = array_merge($this->pageOptions, $options);
        // $options['input'] = ($this->version9 ? '--' : '')."toc";
        $options['input'] = "toc";
        $this->pages[] = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     */
    public function render()
    {
        $tmpFile = $this->createTmpFile();

        $process = $this->buildProcess($tmpFile);

        $process->run();

        if ( ! $process->isSuccessful()) {
            if ( ! file_exists($tmpFile) or filesize($tmpFile) === 0) {
                throw new \RuntimeException('Could not run command:' . $process->getErrorOutput());
            } else {
                throw new \Exception('Error occured while creating PDF.');
            }
        } else {
            $this->tmpFile = $tmpFile;
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

    /**
     * Build command string
     * @param  string $file filename
     * @return string       command string
     */
    public function buildProcess($file)
    {
        $arguments = array_merge(
            $this->buildArguments($this->options, array_merge($this->validOptions, $this->validPageOptions)),
            $this->buildArguments($this->pageOptions, $this->validPageOptions)
        );

        foreach ($this->pages as $page) {
            $arguments = array_merge($arguments, array($page['input']));
            unset($page['input']);
            $arguments = array_merge($arguments, $this->buildArguments($page, $this->validPageOptions));
        }

        $builder = new ProcessBuilder($arguments);
        $builder->setPrefix($this->getConfig('bin', '/usr/bin/wkhtmltopdf'));
        $builder->add($file);

        return $builder->getProcess();
    }

    /**
     * Build command line options from array
     * @param  array  $options      Input parameters
     * @param  array  $validOptions Valid parameter names
     * @return string               argument string
     */
    protected function buildArguments(array $options = array(), array $validOptions = array())
    {
        $arguments = array();
        foreach ($options as $key => $value) {
            // Only include valid options
            $option = is_numeric($key) ? $value : $key;
            if ( ! in_array($option, $validOptions) and ! empty($validOptions)) {
                continue;
            }

            // Is it an option or option-value pair(s)
            if (is_bool($value)) {
               $arguments[] = "--$key";
            } elseif(is_array($value)) {
                foreach ($value as $index => $option) {
                    $arguments[] = "--$key";

                    // Is it an option value or a pair of values
                    if (is_string($index)) {
                        $arguments[] = $index;
                    }

                    $arguments[] = $option;
                }
            } else {
                $arguments[] = "--$key";
                $arguments[] = $value;
            }
        }

        return $arguments;
    }

    /**
     * Get commandline with arguments
     *
     * @param  string $file filename
     * @return string
     */
    public function getCommandLine($file = null)
    {
        $process = $this->buildProcess($file);
        return $process->getCommandLine();
    }

    /**
     * {@inheritdoc}
     */
    public function output($file = null)
    {
        $tmpFile = $this->getPdf();

        if ($tmpFile) {
            $file = $file ?: basename($tmpFile);

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

    /**
     * {@inheritdoc}
     */
    public function download($file = null)
    {
        $this->output($file);

        header("Content-Disposition: attachment; filename=\"$file\"");
    }

    /**
     * {@inheritdoc}
     */
    public function save($file)
    {
        $tmpFile = $this->getPdf();

        if ($tmpFile) {
            copy($tmpFile, $file);
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function raw()
    {
        return file_get_contents($this->getPdf());
    }

    /**
     * Get path of temporary PDF file, render it if necessary
     *
     * @return string
     */
    protected function getPdf()
    {
        return $this->tmpFile ?: $this->render()->tmpFile;
    }

    /**
     * Return the extended help of WkHtmlToPdf
     *
     * @return string
     */
    public function help()
    {
        $builder = new ProcessBuilder(array('--extended-help'));
        $builder->setPrefix($this->getConfig('bin', '/usr/bin/wkhtmltopdf'));
        $process = $builder->getProcess();

        $process->run();

        if ($process->isSuccessful()) {
            return $process->getOutput();
        }
    }
}

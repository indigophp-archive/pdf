<?php

namespace Indigo\Pdf\Adapter;

use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

class WkhtmltopdfAdapter extends AbstractAdapter
{
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
     * Optional options
     *
     * @var array
     */
    protected $optionalOptions = array(
        'page-size'          => 'string',
        'dpi'                => 'integer',
        'grayscale'          => 'bool',
        'lowquality'         => 'bool',
        'image-dpi'          => 'integer',
        'image-quality'      => 'integer',
        'margin-bottom'      => array('integer', 'string'),
        'margin-left'        => array('integer', 'string'),
        'margin-right'       => array('integer', 'string'),
        'margin-top'         => array('integer', 'string'),
        'page-height'        => array('string', 'integer'),
        'page-width'         => array('string', 'integer'),
        'no-pdf-compression' => 'bool',
        'title'              => 'string',
    );

    /**
     * Optional page options
     *
     * @var array
     */
    protected $optionalPageOptions = array(
        'footer-center'                => 'string',
        'footer-font-name'             => 'string',
        'footer-font-size'             => 'integer',
        'footer-html'                  => 'string',
        'footer-left'                  => 'string',
        'footer-line'                  => 'bool',
        'no-footer-line'               => 'bool',
        'footer-right'                 => 'string',
        'footer-spacing'               => array('integer', 'string'),
        'header-center'                => 'string',
        'header-font-name'             => 'string',
        'header-font-size'             => 'integer',
        'header-html'                  => 'string',
        'header-left'                  => 'string',
        'header-line'                  => 'bool',
        'no-header-line'               => 'bool',
        'header-right'                 => 'string',
        'header-spacing'               => array('integer', 'string'),
        'replace'                      => 'array',
        'allow'                        => 'string',
        'background'                   => 'bool',
        'no-background'                => 'bool',
        'checkbox-checked-svg'         => 'string',
        'checkbox-svg'                 => 'string',
        'cookie'                       => 'array',
        'custom-header'                => 'array',
        'custom-header-propagation'    => 'bool',
        'no-custom-header-propagation' => 'bool',
        'debug-javascript'             => 'bool',
        'no-debug-javascript'          => 'bool',
        'default-header'               => 'string',
        'encoding'                     => 'string',
        'disable-external-links'       => 'bool',
        'enable-external-links'        => 'bool',
        'disable-forms'                => 'bool',
        'enable-forms'                 => 'bool',
        'images'                       => 'bool',
        'no-images'                    => 'bool',
        'disable-internal-links'       => 'bool',
        'enable-internal-links'        => 'bool',
        'disable-javascript'           => 'bool',
        'enable-javascript'            => 'bool',
        'javascript-delay'             => 'integer',
        'load-error-handling'          => 'string',
        'disable-local-file-access'    => 'bool',
        'enable-local-file-access'     => 'bool',
        'minimum-font-size'            => 'integer',
        'exclude-from-outline'         => 'bool',
        'include-in-outline'           => 'bool',
        'page-offset'                  => 'integer',
        'password'                     => 'string',
        'disable-plugins'              => 'bool',
        'enable-plugins'               => 'bool',
        'post'                         => 'array',
        'post-file'                    => 'array',
        'print-media-type'             => 'bool',
        'no-print-media-type'          => 'bool',
        'proxy'                        => 'string',
        'radiobutton-checked-svg'      => 'string',
        'radiobutton-svg'              => 'string',
        'run-script'                   => 'string',
        'disable-smart-shrinking'      => 'bool',
        'enable-smart-shrinking'       => 'bool',
        'stop-slow-scripts'            => 'bool',
        'no-stop-slow-scripts'         => 'bool',
        'disable-toc-back-links'       => 'bool',
        'enable-toc-back-links'        => 'bool',
        'user-style-sheet'             => 'string',
        'username'                     => 'string',
        'window-status'                => 'string',
        'zoom'                         => 'float',
    );

    public function __construct(array $options = array(), array $config = array())
    {
        $this->setOptions($options);
        $this->setConfig($config);
    }

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
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $units = $this->filterUnits($this->optionalOptions);

        $normalizers = array_fill_keys($units, $this->unitNormalizer());

        $normalizers['orientation'] = $this->orientationNormalizer();

        $defaults = array_fill_keys($units, 10);

        $defaults['page-size'] = function (Options $options) {
            return $options['size'];
        };

        $resolver
            ->setDefaults($defaults)
            ->setOptional(array_keys($this->optionalOptions))
            ->setAllowedTypes($this->optionalOptions)
            ->setOptional(array_keys($this->optionalPageOptions))
            ->setAllowedTypes($this->optionalPageOptions)
            ->setNormalizers($normalizers)
            ->setAllowedValues(array(
                'orientation' => array('Portrait', 'Landscape')
            ));
    }

    /**
     * {@inheritdoc}
     */
    protected function setDefaultPageOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultPageOptions($resolver);

        $units = $this->filterUnits($this->optionalPageOptions);

        $defaults = array_fill_keys($units, 0);

        $resolver
            ->setDefaults($defaults)
            ->setOptional(array_keys($this->optionalPageOptions))
            ->setAllowedTypes($this->optionalPageOptions)
            ->setAllowedValues(array(
                'load-error-handling' => array('abort', 'ignore', 'skip')
            ));
    }

    /**
     * {@inheritdoc}
     */
    protected function setDefaultConfig(OptionsResolverInterface $resolver)
    {
        parent::setDefaultConfig($resolver);

        $resolver
            ->setDefaults(array(
                'bin'      => '/usr/bin/wkhtmltopdf',
                'tmp'      => '/tmp',
                'version9' => false,
            ))
            ->setAllowedTypes(array(
                'bin'      => 'string',
                'tmp'      => 'string',
                'version9' => 'bool',
            ));
    }

    /**
     * Normalize orientation value
     *
     * @return \Closure
     */
    private function orientationNormalizer()
    {
        return function (Options $options, $value) {
            if ($value == 'L') {
                return 'Landscape';
            } elseif ($value == 'P') {
                return 'Portrait';
            }

            return $value;
        };
    }

    private function filterUnits(array $options)
    {
        $units = array_filter($options, function ($item) {
            return $item == array('integer', 'string');
        });

        return array_keys($units);
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
        $options = $this->resolvePageOptions($options);
        $options['input'] = $this->isFile($input) ? $input : $this->createTmpFile($input);
        $this->pages[] = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addToc(array $options = array())
    {
        // $options['input'] = ($this->version9 ? '--' : '')."toc";
        $options = $this->resolvePageOptions($options);
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
            $content .= $this->isFile($input) ? file_get_contents($input) : $input;
            file_put_contents($page['input'], $content);
        } else {
            return $this->addPage($input);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMargin($left = 0, $top = 0, $right = -1, $bottom = 0)
    {
        $options = array(
            'margin-left' => $left,
            'margin-top' => $top,
            'margin-right' => $right == -1 ? $left : $right,
            'margin-bottom' => $bottom,
        );

        $this->setOptions($options);

        return $this;
    }

    public function setHeader($input, array $options = array())
    {
        $options['header-html'] = $this->isFile($input) ? $input : $this->createTmpFile($input);
        $this->setPageOptions($options);

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

        if (!$process->isSuccessful()) {
            if (!file_exists($tmpFile) or filesize($tmpFile) === 0) {
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
     * @param  mixed $content The file content
     * @return string The path to the created file
     */
    protected function createTmpFile($content = null)
    {
        $tmpPath = $this->getConfig('tmp', sys_get_temp_dir());
        $tmpFile = tempnam($tmpPath,'tmp_WkHtmlToPdf_');

        if (!is_null($content)) {
            file_put_contents($tmpFile, $content);
        }

        $this->tmpFiles[] = $tmpFile;

        return $tmpFile;
    }

    /**
     * Build command
     *
     * @param  string $file filename
     * @return \Symfony\Component\Process\Process Process object
     */
    public function buildProcess($file)
    {
        $validArguments = array_merge($this->optionalOptions, $this->optionalPageOptions);
        $arguments = array_merge(
            $this->buildArguments(array_intersect_key($this->options, $validArguments)),
            $this->buildArguments(array_intersect_key(
                $this->pageOptions,
                $this->optionalPageOptions
            ))
        );

        foreach ($this->pages as $page) {
            $arguments = array_merge($arguments, array($page['input']));
            unset($page['input']);
            $arguments = array_merge($arguments, $this->buildArguments($page));
        }

        $builder = new ProcessBuilder($arguments);
        $builder->setPrefix($this->getConfig('bin', '/usr/bin/wkhtmltopdf'));
        $builder->add($file);

        return $builder->getProcess();
    }

    /**
     * Build command line options from array
     *
     * @param  array  $options      Input parameters
     * @return string               Argument string
     */
    protected function buildArguments(array $options = array())
    {
        $arguments = array();
        foreach ($options as $key => $value) {
            if ($value === false) {
                continue;
            }

            $arguments[] = "--$key";

            if(is_array($value)) {
                foreach ($value as $index => $option) {
                    // Is it an option value or a pair of values
                    if (is_string($index)) {
                        $arguments[] = $index;
                    }

                    $arguments[] = $option;
                }
            } else {
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

            $this->sendHeaders($tmpFile);
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
        $tmpFile = $this->getPdf();

        if ($tmpFile) {
            $file = $file ?: basename($tmpFile);

            $this->sendHeaders($tmpFile);
            header("Content-Disposition: attachment; filename=\"$file\"");

            readfile($tmpFile);

            return true;
        }

        return false;
    }

    /**
     * Send PDF file headers
     *
     * @param  string $tmpFile Temp file path
     */
    protected function sendHeaders($tmpFile)
    {
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/pdf');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($tmpFile));
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

<?php

namespace Indigo\Pdf\Driver;

use Indigo\Pdf\Driver;

class Tcpdf extends Driver
{
    /**
     * @var array pdf driver config defaults
     */
    protected $defaults = array(
        'lang' => array(
            'a_meta_charset'  => 'UTF-8',
            'a_meta_dir'      => 'ltr',
            'a_meta_language' => 'en',
            'w_page'          => 'page',
        ),
        'options'  => array(
            'orientation' => 'P',
            'unit'        => 'mm',
            'page-size'   => 'A4',
            'encoding'    => 'UTF-8',
            'unicode'     => true,
            'diskcache'   => true,
            'pdfa'        => true,
        ),
        'page_options' => array(),
    );

    public function __construct(array $config = array())
    {
        parent::__construct($config);

        $args = array(
            $this->getConfig('orientation', 'P'),
            $this->getConfig('unit', 'mm'),
            $this->getConfig('page-size', 'A4'),
            $this->getConfig('unicode', true),
            $this->getConfig('encoding', 'UTF-8'),
            $this->getConfig('diskcache', false),
            $this->getConfig('pdfa', false),
        );

        $instance = new \ReflectionClass('\TCPDF');
        $this->instance = $instance->newInstanceArgs($args);
        $this->instance->setLanguageArray($this->getConfig('lang', array()));
    }


    public function output($file = 'doc.pdf')
    {
        return $this->instance->Output($file);
    }

    public function download($file = 'doc.pdf')
    {
        return $this->instance->Output($file, 'D');
    }

    public function save($file)
    {
        return $this->instance->Output($file, 'F');
    }

    public function raw()
    {
        return $this->instance->Output(null, 'S');
    }

    public function addPage($input, array $options = array())
    {
        $options = array_merge($this->page_options, $options);

        $orientation = array_key_exists('orientation', $options) ? $options['orientation'] : '';
        $page_size = array_key_exists('page-size', $options) ? $options['page-size'] : '';

        $this->instance->AddPage($orientation, $page_size);

        if (is_file($input)) {
            $input = file_get_contents($input);
        }

        if ($this->isHtml($input)) {
            $this->instance->writeHTML($input);
        } else {
            $this->instance->write($input);
        }

        return $this;
    }

    public function render()
    {
        return $this;
    }
}

<?php

namespace Indigo\Pdf\Adapter;

class Tcpdf extends AbstractAdapter
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
            'unicode'     => true,
            'diskcache'   => true,
            'pdfa'        => true,
        ),
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
        $options = array_merge($this->pageOptions, $options);

        $orientation = array_key_exists('orientation', $options) ? $options['orientation'] : '';
        $page_size = array_key_exists('page-size', $options) ? $options['page-size'] : '';

        $this->instance->AddPage($orientation, $page_size);

        return $this->write($input);
    }

    public function addToc(array $options = array())
    {
        $options = array_merge($this->pageOptions, $options);

        $orientation = array_key_exists('orientation', $options) ? $options['orientation'] : '';
        $page_size = array_key_exists('page-size', $options) ? $options['page-size'] : '';

        $this->instance->AddPage($orientation, $page_size, false, true);

        return $this->write($input);
    }

    public function setTitle($title)
    {
        $this->instance->SetTitle($title);
    }

    public function write($input)
    {
        if (is_file($input) or preg_match('/(https?|file):\/\//', $input)) {
            $input = file_get_contents($input);
        }

        if ($this->isHtml($input)) {
            $this->instance->writeHTML($input);
        } else {
            $this->instance->write($input);
        }

        return $this;
    }

    public function setMargin($left = 0, $top = 0, $right = -1, $bottom = null)
    {
        is_numeric($left) and $this->instance->SetLeftMargin($left);
        is_numeric($top) and $this->instance->SetTopMargin($top);
        is_numeric($right) and $this->instance->SetRightMargin($right == -1 ? $left : $right);
        is_numeric($bottom) and $this->instance->SetAutoPageBreak(true, $bottom);

        return $this;
    }

    public function render()
    {
        return $this;
    }
}

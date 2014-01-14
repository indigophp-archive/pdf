<?php

namespace Indigo\Pdf\Adapter;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TcpdfAdapter extends AbstractAdapter
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

    public function __construct(array $options = array(), array $config = array())
    {
        $this->setOptions($options);
        $this->setConfig($config);

        $this->instance = new \TCPDF(
            $this->options['orientation'],
            $this->options['unit'],
            $this->options['size'],
            $this->options['unicode'],
            $this->options['encoding'],
            $this->options['diskcache'],
            $this->options['pdfa']
        );

        $lang = array(
            $this->config['a_meta_charset'],
            $this->config['a_meta_dir'],
            $this->config['a_meta_language'],
            $this->config['w_page'],
        );

        $this->instance->setLanguageArray($lang);
    }

    /**
     * {@inheritdoc}
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver
            ->setDefaults(array(
                'unicode'   => true,
                'diskcache' => false,
                'pdfa'      => false,
            ))
            ->setAllowedTypes(array(
                'unicode'   => 'bool',
                'diskcache' => 'bool',
                'pdfa'      => 'bool',
            ));
    }

    /**
     * {@inheritdoc}
     */
    protected function setDefaultPageOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultPageOptions($resolver);

        $resolver
            ->setDefaults(array(
                'orientation' => $this->options['orientation'],
                'size'        => $this->options['size'],
            ))
            ->setAllowedValues(array(
                'orientation' => array('P', 'L'),
            ))
            ->setAllowedTypes(array(
                'orientation' => 'string',
                'size'        => 'string',
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
                'a_meta_charset'  => 'UTF-8',
                'a_meta_dir'      => 'ltr',
                'a_meta_language' => 'en',
                'w_page'          => 'page',
            ))
            ->setAllowedTypes(array(
                'a_meta_charset'  => 'string',
                'a_meta_dir'      => 'string',
                'a_meta_language' => 'string',
                'w_page'          => 'string',
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function addPage($input, array $options = array())
    {
        $options = $this->resolvePageOptions($options);

        $this->instance->AddPage($options['orientation'], $options['size']);

        return $this->write($input);
    }

    /**
     * {@inheritdoc}
     */
    public function addToc(array $options = array())
    {
        $options = $this->resolvePageOptions($options);

        $this->instance->AddPage($options['orientation'], $options['size'], false, true);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setTitle($title)
    {
        $this->instance->SetTitle($title);
    }

    /**
     * {@inheritdoc}
     */
    public function write($input)
    {
        if ($this->isFile($input)) {
            $input = file_get_contents($input);
        }

        if ($this->isHtml($input)) {
            $this->instance->writeHTML($input);
        } else {
            $this->instance->write($input);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMargin($left = 0, $top = 0, $right = -1, $bottom = null)
    {
        $this->instance->SetLeftMargin($left);
        $this->instance->SetTopMargin($top);
        $this->instance->SetRightMargin($right == -1 ? $left : $right);
        $this->instance->SetAutoPageBreak(true, $bottom);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function output($file = 'doc.pdf')
    {
        return $this->instance->Output($file);
    }

    /**
     * {@inheritdoc}
     */
    public function download($file = 'doc.pdf')
    {
        return $this->instance->Output($file, 'D');
    }

    /**
     * {@inheritdoc}
     */
    public function save($file)
    {
        return $this->instance->Output($file, 'F');
    }

    /**
     * {@inheritdoc}
     */
    public function raw()
    {
        return $this->instance->Output(null, 'S');
    }
}

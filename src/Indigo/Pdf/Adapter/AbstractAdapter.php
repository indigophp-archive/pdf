<?php

namespace Indigo\Pdf\Adapter;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * Options
     *
     * @var array
     */
    protected $options = array();

    /**
     * Default page options
     *
     * @var array
     */
    protected $pageOptions = array();

    /**
     * Config
     *
     * @var array
     */
    protected $config = array();

    /**
     * PDF library instance
     *
     * @var mixed
     */
    protected $instance = null;

    /**
     * Get a specific or all options
     *
     * @param  string $key     Option key
     * @param  mixed  $default Default return value if key is not found
     * @return mixed Key, default value or array of all options
     */
    public function getOptions($key = null, $default = null)
    {
        return $this->arrGet($this->options, $key, $default);
    }

    /**
     * Set an array of options
     *
     * @param array $options
     */
    public function setOptions(array $options = array())
    {
        $this->options = $this->resolveOptions($options);

        return $this;
    }

    /**
     * Resolve options
     *
     * @param  array  $options
     * @return Options
     */
    protected function resolveOptions(array $options = array())
    {
        $resolver = new OptionsResolver;
        $this->setDefaultOptions($resolver);

        if (!empty($this->options)) {
            $resolver->setDefaults($this->options);
        }

        return $resolver->resolve($options);
    }

    /**
     * Set default options
     *
     * @param OptionsResolverInterface $resolver
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(array(
                'orientation' => 'P',
                'page-size'   => 'A4',
                'unit'        => 'mm',
                'encoding'    => 'UTF-8',
            ))
            ->setAllowedValues(array(
                'orientation' => array('P', 'L'),
            ))
            ->setAllowedTypes(array(
                'orientation' => 'string',
                'page-size'   => 'string',
                'unit'        => 'string',
                'encoding'    => 'string',
            ));
    }

    /**
     * Get a specific or all page options
     *
     * @param  string $key     Page option key
     * @param  mixed  $default Default return value if key is not found
     * @return mixed Key, default value or array of all page options
     */
    public function getPageOption($key = null, $default = null)
    {
        return $this->arrGet($this->pageOptions, $key, $default);
    }

    /**
     * Set an array of page options
     *
     * @param array $options
     */
    public function setPageOptions(array $options = array())
    {
        $this->pageOptions = $this->resolvePageOptions($options);

        return $this;
    }

    /**
     * Resolve page options
     *
     * @param  array  $options
     * @return Options
     */
    protected function resolvePageOptions(array $options = array())
    {
        $resolver = new OptionsResolver;
        $this->setDefaultPageOptions($resolver);

        if (!empty($this->pageOptions)) {
            $resolver->setDefaults($this->pageOptions);
        }

        return $resolver->resolve($options);
    }

    /**
     * Set default page options
     *
     * @param OptionsResolverInterface $resolver
     */
    protected function setDefaultPageOptions(OptionsResolverInterface $resolver)
    {

    }

    /**
     * Get a specific or all config items
     *
     * @param  string $key     Config key
     * @param  mixed  $default Default return value if key is not found
     * @return mixed Key, default value or array of all config items
     */
    public function getConfig($key = null, $default = null)
    {
        return $this->arrGet($this->config, $key, $default);
    }

    /**
     * Set an array of config items
     *
     * @param array $config
     */
    public function setConfig(array $config = array())
    {
        $this->config = $this->resolveConfig($config);

        return $this;
    }

    /**
     * Resolve config
     *
     * @param  array  $config
     * @return Options
     */
    protected function resolveConfig(array $config = array())
    {
        $resolver = new OptionsResolver;
        $this->setDefaultConfig($resolver);

        if (!empty($this->config)) {
            $resolver->setDefaults($this->config);
        }

        return $resolver->resolve($config);
    }

    /**
     * Set default config
     *
     * @param OptionsResolverInterface $resolver
     */
    protected function setDefaultConfig(OptionsResolverInterface $resolver)
    {

    }

    protected function unitNormalizer()
    {
        return function (Options $options, $value) {
            if (is_int($value)) {
                $value .= $options['unit'];
            } elseif (empty($value)) {
                $value = '';
            }

            return $value;
        };
    }

    public function getInstance()
    {
        return $this->instance ?: $this;
    }

    protected function isHtml($string)
    {
        return preg_match("/<[^<]+>/", $string);
    }

    protected function isFile($string)
    {
        if (is_file($string)) {
            return true;
        } elseif (filter_var($string, FILTER_VALIDATE_URL) !== false) {
            return true;
        }

        return false;
    }

    protected function arrGet(array $array, $key = null, $default = null)
    {
        if (is_null($key)) {
            return $array;
        } elseif (array_key_exists($key, $array)) {
            return $array[$key];
        } else {
            return $default;
        }
    }

    protected function map(array $map = array(), array $values = array())
    {
        foreach ($map as $key => $value) {
            if (array_key_exists($key, $values)) {
                if (is_array($value)) {
                    $values[$key] = str_replace($value[0], $value[1], $values[$key]);
                }
                else
                {
                    $values[$value] = $values[$key];
                    unset($values[$key]);
                }
            }
        }

        return $values;
    }
}
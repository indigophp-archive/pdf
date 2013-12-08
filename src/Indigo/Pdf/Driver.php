<?php

namespace Indigo\Pdf;

use Indigo\Pdf\PdfInterface;

abstract class Driver implements PdfInterface
{
	private $globalDefaults = array(
		'options' => array(
            'orientation' => 'P',
            'page-size'   => 'A4',
            'unit'        => 'mm',
            'encoding'    => 'UTF-8',
		),
		'page_options' => array(),
	);

	protected $defaults = array();

	protected $config = array();

	protected $options = array();

	protected $mapOptions = array();

	protected $pageOptions = array();

	protected $instance = null;

	public function __construct(array $config = array())
	{
		$config = array_merge_recursive($this->globalDefaults, $this->defaults, $config);

		if (array_key_exists('options', $config)) {
			$this->options = $this->map($this->mapOptions, $config['options']);
			unset($config['options']);
		}

		if (array_key_exists('page_options', $config)) {
			$this->pageOptions = $this->map($this->mapOptions, $config['page_options']);
			unset($config['page_options']);
		}

		$this->config = $config;
	}

	public function getConfig($key = null, $default = null)
	{
		return $this->arrGet($this->config, $key, $default);
	}

	public function getOption($key, $default = null)
	{
		return $this->arrGet($this->options, $key, $default);
	}

	public function getInstance()
	{
		return $this->instance ?: $this;
	}

    protected function isHtml($string)
    {
        // return $string != strip_tags($string);
        return preg_match("/<[^<]+>/", $string);
    }

    protected function arrGet(array $array, $key = null, $default = null)
    {
		return is_null($key) ? $array : (array_key_exists($key, $array) ? $array[$key] : $default);
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
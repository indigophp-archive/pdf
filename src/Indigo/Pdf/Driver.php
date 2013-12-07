<?php

namespace Indigo\Pdf;

use Indigo\Pdf\PdfInterface;

abstract class Driver implements PdfInterface
{
	private $globalDefaults = array();

	protected $defaults = array();

	protected $config = array();

	protected $options = array();

	protected $page_options = array();

	protected $instance = null;

	public function __construct(array $config = array())
	{
		$config = array_merge_recursive($this->globalDefaults, $this->defaults, $config);

		if (array_key_exists('options', $config)) {
			$this->options = $config['options'];
			unset($config['options']);
		}

		if (array_key_exists('page_options', $config)) {
			$this->page_options = $config['page_options'];
			unset($config['page_options']);
		}

		$this->config = $config;
	}

	public function getConfig($key, $default = null)
	{
		return array_key_exists($key, $this->config) ? $this->config[$key] : $default;
	}

	public function getInstance()
	{
		return $this->instance;
	}

    protected function isHtml($string)
    {
        return $string != strip_tags($string);
    }
}
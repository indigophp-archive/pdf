<?php

namespace Indigo\Pdf;

interface PdfInterface
{
	public function addPage($input, array $options = array());

	public function render();

	public function output($file = null);

	public function save($file);

	/**
	 * Force the browser to download PDF file
	 * @param  string $file optional name of file
	 * @return bool
	 */
	public function download($file = null);

	/**
	 * Return a raw representation of PDF file
	 * @return string
	 */
	public function raw();
}
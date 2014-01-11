<?php

namespace Indigo\Pdf\Adapter;

interface AdapterInterface
{
    /**
     * Add a page with specific content
     *
     * @param  string $input   HTML code or URL
     * @param  array  $options page options
     * @return AdapterInterface
     */
    public function addPage($input, array $options = array());

    /**
     * Set title of PDF
     *
     * @param string $title
     */
    public function setTitle($title);

    /**
     * Write content without adding a new page
     *
     * @param  string $input content to write
     * @return AdapterInterface
     */
    public function write($input);

    /**
     * Add Table of Content
     *
     * @param  array  $options page options
     * @return AdapterInterface
     */
    public function addToc(array $options = array());

    /**
     * Set margins of the document
     *
     * @param integer $left
     * @param integer $top
     * @param integer $right
     * @param integer $bottom
     * @return AdapterInterface
     */
    public function setMargin($left = 0, $top = 0, $right = -1, $bottom = 0);

    /**
     * Render the file itself
     *
     * @return AdapterInterface
     */
    public function render();

    /**
     * Output the file to the browser
     *
     * @param  string $file name of file in case of download
     * @return bool
     */
    public function output($file = null);

    /**
     * Save the file with a specific path and name
     *
     * @param  string $file file path and name
     * @return bool
     */
    public function save($file);

    /**
     * Force the browser to download PDF file
     *
     * @param  string $file optional name of file
     * @return bool
     */
    public function download($file = null);

    /**
     * Return a raw string of PDF file
     *
     * @return string
     */
    public function raw();
}

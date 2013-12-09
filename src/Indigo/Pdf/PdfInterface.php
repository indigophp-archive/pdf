<?php

namespace Indigo\Pdf;

interface PdfInterface
{
    /**
     * Add a page with specific content
     *
     * @param  string $input   HTML code or URL
     * @param  array  $options page options
     * @return PdfInterface
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
     * @return PdfInterface
     */
    public function write($input);

    /**
     * Add Table of Content
     *
     * @param  array  $options page options
     * @return PdfInterface
     */
    public function addToc(array $options = array());

    /**
     * Render the file itself
     *
     * @return PdfInterface
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

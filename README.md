Indigo PDF
==========

PDF adapters for PDF libraries.

Supported libraries:
* WkHTMLtoPDF (native)
* TCPDF
* DOMPDF (soon)
* mPDF (soon)

The adapters only implement a basic interface, so any of these adapters can be used in a DiC.

Usage
-----

```php

use Indigo\Pdf\Driver\Tcpdf as Pdf;

// Setup config array
$config = array(
	'options' => array(
		'orientation' => 'P',
		'page-size'   => 'A4'
	)
);

// Instantiate adapter
$pdf = new Pdf($config);

// Add a page
$pdf->addPage('test.html', array('orientation' => 'L'));

// Save it to file
$pdf->save('test.pdf');

// Output to the browser
$pdf->output('test.pdf');
```

If you need to use the library itself, you can get the instance with ````$pdf->getInstance()````.
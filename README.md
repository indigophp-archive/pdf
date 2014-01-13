# Indigo PDF

**PDF adapters for PDF libraries.**

Supported libraries:
* WkHTMLtoPDF (native)
* TCPDF


## Install

Via Composer

``` json
{
    "require": {
        "indigophp/pdf": "dev-master"
    }
}
```


## Usage

``` php
use Indigo\Pdf\Adapter\TcpdfAdapter as Pdf;

// Setup config array
$options = array(
    'orientation' => 'P',
    'size'        => 'A4'
);

// Instantiate adapter
$pdf = new Pdf($options);

// Add a page
$pdf->addPage('test.html', array('orientation' => 'L'));

// Save it to file
$pdf->save('test.pdf');

// Output to the browser
$pdf->output('test.pdf');
```

**Note:** This is only a basic interface. If you need advanced usage, get the library itself and use that: `$pdf->getInstance()`


## Library documentation

* [WkHTMLtoPDF](http://madalgo.au.dk/~jakobt/wkhtmltoxdoc/wkhtmltopdf_0.10.0_rc2-doc.html) (This is the best I found)
* [TCPDF](http://www.tcpdf.org/)


## Testing

``` bash
$ phpunit
```


## Contributing

Please see [CONTRIBUTING](https://github.com/indigophp/pdf/blob/develop/CONTRIBUTING.md) for details.


## Credits

- [Márk Sági-Kazár](https://github.com/sagikazarmark)
- [All Contributors](https://github.com/indigophp/pdf/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/indigophp/pdf/blob/develop/LICENSE) for more information.
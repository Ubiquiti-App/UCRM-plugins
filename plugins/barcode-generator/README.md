# Barcode generator
This plugin generates SVG image of one or two dimensional barcode which can be included into financial templates.

## Templates implementation
You can add barcode image into template code e.g.:
``<img src="https://www.example.com/crm/_plugins/barcode-generator/public.php?token=USECONFIGURATIONTOKEN&code=DI{{ invoice.number }}{{ totals.totalRaw }}&type=QRCODE&width=200&height=200&color=black">``
> Server must be secured with valid certificate for https request. 

## Required parameters
- `token` - Security token from the plugin configuration in the CRM
- `code` - Content of the barcode
- `type` - Type of barcode
- `with` - With of barcode in pixels
- `height` - Height of barcode in pixels
- `color` - Color of barcode

## Parameter `token`
Due to security reasons fill some random string as a token and use it as a part of request.

## Parameter `type`
Types of barcode:

* C39        : CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9
* C39+       : CODE 39 with checksum
* C39E       : CODE 39 EXTENDED
* C39E+      : CODE 39 EXTENDED + CHECKSUM
* C93        : CODE 93 - USS-93
* S25        : Standard 2 of 5
* S25+       : Standard 2 of 5 + CHECKSUM
* I25        : Interleaved 2 of 5
* I25+       : Interleaved 2 of 5 + CHECKSUM
* C128       : CODE 128
* C128A      : CODE 128 A
* C128B      : CODE 128 B
* C128C      : CODE 128 C
* EAN2       : 2-Digits UPC-Based Extension
* EAN5       : 5-Digits UPC-Based Extension
* EAN8       : EAN 8
* EAN13      : EAN 13
* UPCA       : UPC-A
* UPCE       : UPC-E
* MSI        : MSI (Variation of Plessey code)
* MSI+       : MSI + CHECKSUM (modulo 11)
* POSTNET    : POSTNET
* PLANET     : PLANET
* RMS4CC     : RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)
* KIX        : KIX (Klant index - Customer index)
* IMB        : IMB - Intelligent Mail Barcode - Onecode - USPS-B-3200
* IMBPRE     : IMB - Intelligent Mail Barcode - Onecode - USPS-B-3200- pre-processed
* CODABAR    : CODABAR
* CODE11     : CODE 11
* PHARMA     : PHARMACODE
* PHARMA2T   : PHARMACODE TWO-TRACKS
* DATAMATRIX : DATAMATRIX (ISO/IEC 16022)
* PDF417     : PDF417 (ISO/IEC 15438:2006)
* QRCODE     : QR-CODE
* RAW        : 2D RAW MODE comma-separated rows
* RAW2       : 2D RAW MODE rows enclosed in square parentheses

## Example of code parameter
### QRCODE - "QR Platba" in the Czechia
``<img src="https://www.example.com/crm/_plugins/barcode-generator/public.php?token=RANDOMtokenFROMconfiguration&code=SPD*1.0*ACC:CZ2806000000000168540115*AM:{{ totals.totalRaw }}*CC:CZK*MSG:{{ client.name }}*X-VS:{{ invoice.number }}&type=QRCODE&width=75&height=75&color=black">``

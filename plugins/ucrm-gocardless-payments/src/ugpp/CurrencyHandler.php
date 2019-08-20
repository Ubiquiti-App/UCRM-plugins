<?php 
declare(strict_types=1);
namespace Ugpp;

class CurrencyHandler {
  public $zero_decimal_currencies = ["mga", "bif", "clp", "pyg", "djf", "rwf", "gnf", "ugx", "jpy", "vnd", "vuv", "xaf", "kmf", "krw", "xof", "xpf"];

  public function notZeroDecimal($currency) {
    $currency = strtolower($currency);
    if (in_array($currency, $this->zero_decimal_currencies)) {
      return false;
    } else {
      return true;
    }
  }

}
<?php
$payload = @file_get_contents("php://input");

$interpreter = new \Ucsp\Interpreter();
$result = $interpreter->run($payload);
if ($result) {
  echo $result;
  exit();
}
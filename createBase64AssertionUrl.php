<?php
if (! isset($argv[1])) {
    echo "Error: Please pass one argument for base64 encoding / decoding \n";
    exit;
}

echo 'Raw string:   \'' . $argv[1] . "'\n";
echo 'AssertionURL: \'' . base64_encode($argv[1]) . "'\n";
?>


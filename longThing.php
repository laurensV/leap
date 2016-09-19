<?php
$file = 'output.txt';
// Open the file to get existing content
// Write the contents back to the file
for ($i=0; $i <= 20; $i++) {
    file_put_contents($file, $i);
    sleep(3); // this should halt for 3 seconds for every loop
}
?>
<p class="lead">This will becomes Laurens' personal site.</p>
<?php

    $domainNames = array("DOUG.NS.CLOUDFLARE.COM", "google.com");

    foreach ($domainNames as $url){
        echo gethostbyname($url); 
    }   
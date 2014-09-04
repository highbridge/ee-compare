<?php include "navigation.php"; ?>

<p><em>Hello, welcome to the dashboard.</em></p>

<div>
    <?php

    if($dev_upload)
        echo "A development export was last uploaded on ". date ("F d Y H:i:s.", $dev_upload);
    else
        echo "A development export has not been uploaded yet!";
        
    ?>
</div>

<div>
    <?php
    
    if($prod_upload)
        echo "A production export was last uploaded on ". date ("F d Y H:i:s.", $prod_upload);
    else
        echo "A production export has not been uploaded yet!";

    ?>
</div>

<div>
    <?php

    if($statistics)
        print_r($statistics);

    ?>
</div>

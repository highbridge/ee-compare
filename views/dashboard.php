<?php include "navigation.php"; ?>

<style>
    .key .new-dev { background-color: rgba(93, 207, 5, 1); }
    .key .new-prod { background-color: rgba(255, 239, 0, 1); }
    .key .changed { background-color: rgba(233, 16, 5, 1); }

    table .new-dev { background-color: rgba(93, 207, 5, 0.5); }
    table .new-prod { background-color: rgba(255, 239, 0, 0.5); }
    table .changed { background-color: rgba(233, 16, 5, 0.5); }

    .table-wrap { display: none; }
    h1:hover { text-decoration: underline; cursor: pointer; }
</style>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

<script>
    $(document).ready(function()
    {
        $('body').on('click', '.data-wrap h1', function()
        {
            $(this).parents('.data-wrap').find('.table-wrap').slideToggle();
        });
    });
</script>

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

<hr>

<div class="key">
    <strong>Key</strong>

    <div style="line-height: 25px; clear:both;">
        <div class="new-dev" style="width: 50px; height: 25px; float: left; margin-right: 4px;"></div>
        New data from dev
    </div>

    <div style="line-height: 25px; clear:both;">
        <div class="new-prod" style="width: 50px; height: 25px; float: left; margin-right: 4px;"></div>
        New data from prod
    </div>

    <div style="line-height: 25px; clear:both;">
        <div class="changed" style="width: 50px; height: 25px; float: left; margin-right: 4px;"></div>
        Changes in existing data
    </div>
</div>


<div>
    <?php

    if($differences)
    {
        echo "<hr>";
        
        foreach($differences as $type => $data)
        {
            echo "<div class='data-wrap'>";
            echo "<h1>".ucwords(str_replace("_", " ", $type))."</h1>";

            echo "<div class='table-wrap'><table>";
            echo "<tr>";
            echo "<th><!-- Empty heading for dev/prod marker --></th>";
            foreach($data['columns'] as $column)
            {
                echo "<th>$column</th>";
            }
            echo "</tr>";

            foreach($data['rows'] as $row)
            {
                $dev_row = "<td>dev</td>";
                $prod_row = "<td>prod</td>";
                
                foreach($data['columns'] as $column)
                {
                    if(!empty($row['dev']) && !empty($row['prod']) && $row['dev'][$column] != $row['prod'][$column])
                        $class = "changed";
                    else
                        $class = "";
                        
                    $dev_row .= "<td class='$class'>".$row['dev'][$column]."</td>";
                    $prod_row .= "<td class='$class'>".$row['prod'][$column]."</td>";
                }

/*
                if($row['changed'])
                    $class = "changed";
                else
                    $class = "";
*/
                $class = "";

                if(empty($row['prod']))
                    $class = 'new-dev';

                if(empty($row['dev']))
                    $class = 'new-prod';
                    
                echo "<tr class='$class'>$dev_row</tr>";
                echo "<tr class='$class'>$prod_row</tr>";
                echo "<tr><td colspan='".(count($data['columns']) + 1)."'><hr></td></tr>";
            }
            echo "</table></div>";
            echo "</div>";
        }        
    }
    ?>
</div>

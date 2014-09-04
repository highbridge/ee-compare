<nav>
    <?php

    $actions = array('dashboard', 'upload', 'logout');

    foreach($actions as $count => $action)
    {
        if(strtolower($_GET['action']) == $action)
            $actions[$count] = "<b>".ucfirst($action)."</b>";
        else
            $actions[$count] = "<a href='$site_url?action=$action'>".ucfirst($action)."</a>";
    }

    echo implode(' | ', $actions);

    ?>

    <hr>
</nav>

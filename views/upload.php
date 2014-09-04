<?php include "navigation.php"; ?>

<form action="<?php echo $site_url; ?>?action=upload" method="post" enctype="multipart/form-data">
    <p><em>You can submit one or both files at the same time.</em></p>
    
    <div>Export from Development: <input type="file" name="file_1"></div>
    <div>Export from Production: <input type="file" name="file_2"></div>
    <input type="submit" value="Upload">
</form>

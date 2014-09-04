<?php

class Model
{
    function __construct()
    {
        // Connect to MySQL
        $this->dev_db = new PDO("mysql:host=".MYSQL_HOST.";dbname=".DATABASE_1, MYSQL_USER, MYSQL_PASS);
        $this->prod_db = new PDO("mysql:host=".MYSQL_HOST.";dbname=".DATABASE_2, MYSQL_USER, MYSQL_PASS);
    }
    
    // Import data from uploaded files into SQL
    function import()
    {
        // Clear existing databases
        $dev_query = $this->dev_db->prepare("SELECT concat('DROP TABLE IF EXISTS ', table_name, ';')
                                              FROM information_schema.tables
                                              WHERE table_schema = '".DATABASE_1."';");
        $dev_query->execute();
        $dev_results = $dev_query->fetchAll();

        foreach($dev_results as $result)
        {
            $dev_tables .= $result[0];
        }

        if($dev_tables)
        {
            $dev_query = $this->dev_db->prepare($dev_tables);
            $dev_query->execute();
        }

        $prod_query = $this->prod_db->prepare("SELECT concat('DROP TABLE IF EXISTS ', table_name, ';')
                                               FROM information_schema.tables
                                               WHERE table_schema = '".DATABASE_2."';");
        $prod_query->execute();
        $prod_results = $prod_query->fetchAll();

        foreach($prod_results as $result)
        {
            $prod_tables .= $result[0];
        }

        if($prod_tables)
        {
            $prod_query = $this->prod_db->prepare($prod_tables);
            $prod_query->execute();
        }

        // If dev file exists, import
        if(file_exists('uploads/development_export.sql'))
        {
            exec("/usr/bin/mysql -u".escapeshellcmd(MYSQL_USER)." -p".escapeshellcmd(MYSQL_PASS)." -h".escapeshellcmd(MYSQL_HOST)." ".escapeshellcmd(DATABASE_1)." < uploads/development_export.sql");
        }
        
        // If prod file exists, import
        if(file_exists('uploads/production_export.sql'))
        {
            exec("/usr/bin/mysql -u".escapeshellcmd(MYSQL_USER)." -p".escapeshellcmd(MYSQL_PASS)." -h".escapeshellcmd(MYSQL_HOST)." ".escapeshellcmd(DATABASE_2)." < uploads/production_export.sql");
        }
    }
}

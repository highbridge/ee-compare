<?php

class Model
{
    function __construct()
    {
        // Connect to MySQL
        $this->dev = new PDO("mysql:host=".MYSQL_HOST.";dbname=".DATABASE_1, MYSQL_USER, MYSQL_PASS);
        $this->prod = new PDO("mysql:host=".MYSQL_HOST.";dbname=".DATABASE_2, MYSQL_USER, MYSQL_PASS);
    }
    
    // Import data from uploaded files into SQL
    function import()
    {
        // Clear existing databases
        $dev_query = $this->dev->prepare("SELECT concat('DROP TABLE IF EXISTS ', table_name, ';')
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
            $dev_query = $this->dev->prepare($dev_tables);
            $dev_query->execute();
        }

        $prod_query = $this->prod->prepare("SELECT concat('DROP TABLE IF EXISTS ', table_name, ';')
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
            $prod_query = $this->prod->prepare($prod_tables);
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
            echo "/usr/bin/mysql -u".escapeshellcmd(MYSQL_USER)." -p".escapeshellcmd(MYSQL_PASS)." -h".escapeshellcmd(MYSQL_HOST)." ".escapeshellcmd(DATABASE_2)." < uploads/production_export.sql<hr/>";

        }
    }

    // General function for querying the database
    function get($db, $query, $data = array())
    {
        if($db == "dev" || $db == "prod")
        {
            $query = $this->$db->prepare($query);
            $query->execute($data);

            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // Function to compare sets of data
    function compare($column, $dev, $prod, $display = false)
    {
        // Prepare dev data
        $dev_primary = array();
        $dev_data = array();

        foreach($dev as $key => $row)
        {
            $dev_primary[$key] = $row[$column];
            $dev_data[$key] = $row;
        }

        // Prepare prod data
        $prod_primary = array();
        $prod_data = array();

        foreach($prod as $key => $row)
        {
            $prod_primary[$key] = $row[$column];
            $prod_data[$key] = $row;
        }

        // Unique to dev
        $dev_unique = array_diff($dev_primary, $prod_primary);
        
        // Unique to prod
        $prod_unique = array_diff($prod_primary, $dev_primary);
        
        // Find rows which exist on both dev and prod
        $matched = array_intersect($dev_primary, $prod_primary);
        $changed = array();
        $unchanged = 0;
        $total = 0;

        foreach($matched as $dev_row => $match)
        {
            $prod_row = array_search($match, $prod_primary);
            $different = false;
            $output = array('cols' => array(), 'dev' => array(), 'prod' => array());
            
            foreach($dev_data[$dev_row] as $col => $value)
            {
                if($display && !in_array($col, $display))
                    continue;

                if($prod_data[$prod_row][$col] != $value)
                    $different = true;

                $output['cols'][] = $col;
                $output['dev'][] = $value;
                $output['prod'][] = $prod_data[$prod_row][$col];
            }

            // Matched, but different
            if($different)
            {
                $changed[] = $output;
            }
            
            // Matched, but the same
            else
            {
                $unchanged++;
            }

            $total++;
        }
        
        return array
        (
            'unique' => array
            (
                'dev' => $dev_unique,
                'prod' => $prod_unique
            ), 
            'changed' => $changed,
            'unchanged' => $unchanged,
            'total' => $total
        );
    }

    // Function to find and output all differences
    function find_differences()
    {
        $different = array();
// MOST IMPORTANT

        // Compare categories
        $dev_categories = $this->get('dev', 'select * from `exp_categories`');
        $prod_categories = $this->get('prod', 'select * from `exp_categories`');

        $different['categories'] = $this->compare('cat_url_title', $dev_categories, $prod_categories);
        
        // Compare category fields
        $dev_category_fields = $this->get('dev', 'select * from `exp_category_fields`');
        $prod_category_fields = $this->get('prod', 'select * from `exp_category_fields`');

        $different['category_fields'] = $this->compare('field_name', $dev_category_fields, $prod_category_fields);

        // Compare fields
        $dev_fields = $this->get('dev', 'select * from `exp_channel_fields`');
        $prod_fields = $this->get('prod', 'select * from `exp_channel_fields`');

        $different['fields'] = $this->compare('field_name', $dev_fields, $prod_fields);
        
        // Compare member fields
        // Compare templates
        $dev_templates = $this->get('dev', 'select *, concat(tg.group_name, "/", t.template_name) as template_path from `exp_templates` as t, `exp_template_groups` as tg where t.group_id = tg.group_id');
        $prod_templates = $this->get('prod', 'select *, concat(tg.group_name, "/", t.template_name) as template_path from `exp_templates` as t, `exp_template_groups` as tg where t.group_id = tg.group_id');

        $different['templates'] = $this->compare('template_path', $dev_templates, $prod_templates, array('template_id', 'template_path', 'allow_php', 'php_parse_location'));

// TODO:
        // Compare actions
        // Compare channels
        // Compare member groups
        // Compare modules
        // Compare fieldtypes
        // Compare statuses
        
        return $different;
    }
}

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

        foreach($dev as $row => $data)
        {
            $dev_primary[$row] = $data[$column];
            $dev_data[$row] = $data;
        }

        // Prepare prod data
        $prod_primary = array();
        $prod_data = array();

        foreach($prod as $row => $data)
        {
            $prod_primary[$row] = $data[$column];
            $prod_data[$row] = $data;
        }

        $columns = array();
        $output_data = array();
        $checked = array();

        // Loop through all dev data to find changes
        foreach($dev_data as $dev_row => $data)
        {
            // Search prod_primary for the requested column
            $prod_row = array_search($data[$column], $prod_primary);
            $row = array('dev' => array(), 'prod' => array(), 'changed' => false);
            
            foreach($data as $field => $value)
            {
                // If display is set, only process requested fields
                if($display && !in_array($field, $display))
                    continue;

                // If this is a new column, add it to the array!
                if(!in_array($field, $columns))
                    $columns[] = $field;

                $row['dev'][$field] = $value;

                // Only compare/save prod data if a matching row was found
                if($prod_row !== false)
                {
                    if($prod_data[$prod_row][$field] != $value)
                        $row['changed'] = true;

                    $row['prod'][$field] = $prod_data[$prod_row][$field];
                }
            }

            $output_data[] = $row;

            // Save the prod row if found so we know which have already been checked
            if($prod_row !== false)
                $checked[$prod_row] = true;
        }

        // Now loop through all prod data to see if we missed anything
        foreach($prod_data as $prod_row => $data)
        {
            // Only process rows that haven't been checked already (this loop should only have new data from prod)
            if(!$checked[$prod_row])
            {
                $row = array('dev' => array(), 'prod' => array(), 'changed' => false);
            
                foreach($data as $field => $value)
                {
                    // If display is set, only process requested fields
                    if($display && !in_array($field, $display))
                        continue;

                    // If this is a new column, add it to the array!
                    if(!in_array($field, $columns))
                        $columns[] = $field;

                    $row['prod'][$field] = $value;
                }

                $output_data[] = $row;
            }
        }
        
        // Desired return data...
        // All columns
        // Rows
        // - Dev
        // - Prod
        // - Is changed?
            
        return array
        (
            'columns' => $columns,
            'rows' => $output_data
        );
    }

    // Function to find and output all differences
    function find_differences()
    {
        $different = array();

        // Compare actions
        $dev_actions = $this->get('dev', 'select *, concat(class, "::", method) as action from `exp_actions`');
        $prod_actions = $this->get('prod', 'select *, concat(class, "::", method) as action from `exp_actions`');

        $different['actions'] = $this->compare('action', $dev_actions, $prod_actions);


        // Compare categories
        $dev_categories = $this->get('dev', 'select * from `exp_categories`');
        $prod_categories = $this->get('prod', 'select * from `exp_categories`');

        $different['categories'] = $this->compare('cat_url_title', $dev_categories, $prod_categories);

        
        // Compare category fields
        $dev_category_fields = $this->get('dev', 'select * from `exp_category_fields`');
        $prod_category_fields = $this->get('prod', 'select * from `exp_category_fields`');

        $different['category_fields'] = $this->compare('field_name', $dev_category_fields, $prod_category_fields);


        // Compare channels
        $dev_channels = $this->get('dev', 'select * from `exp_channels`');
        $prod_channels = $this->get('prod', 'select * from `exp_channels`');

        $different['channels'] = $this->compare('channel_name', $dev_channels, $prod_channels, array('channel_id', 'channel_name', 'channel_title'));


        // Compare channel fields
        $dev_channel_fields = $this->get('dev', 'select * from `exp_channel_fields`');
        $prod_channel_fields = $this->get('prod', 'select * from `exp_channel_fields`');

        $different['channel_fields'] = $this->compare('field_name', $dev_channel_fields, $prod_channel_fields);


        // Compare fieldtypes?

        
        // Compare member fields
        $dev_member_fields = $this->get('dev', 'select * from `exp_member_fields`');
        $prod_member_fields = $this->get('prod', 'select * from `exp_member_fields`');

        $different['member_fields'] = $this->compare('field_name', $dev_member_fields, $prod_member_fields);


        // Compare member groups
        $dev_member_groups = $this->get('dev', 'select * from `exp_member_groups`');
        $prod_member_groups = $this->get('prod', 'select * from `exp_member_groups`');

        $different['member_groups'] = $this->compare('field_name', $dev_member_groups, $prod_member_groups);



        // Compare modules?


        // Compare statuses
        $dev_statuses = $this->get('dev', 'select * from `exp_statuses`');
        $prod_statuses = $this->get('prod', 'select * from `exp_statuses`');

        $different['statuses'] = $this->compare('cat_url_title', $dev_statuses, $prod_statuses);


        // Compare templates
        $dev_templates = $this->get('dev', 'select *, concat(tg.group_name, "/", t.template_name) as template_path from `exp_templates` as t, `exp_template_groups` as tg where t.group_id = tg.group_id');
        $prod_templates = $this->get('prod', 'select *, concat(tg.group_name, "/", t.template_name) as template_path from `exp_templates` as t, `exp_template_groups` as tg where t.group_id = tg.group_id');

        $different['templates'] = $this->compare('template_path', $dev_templates, $prod_templates, array('template_id', 'template_path', 'allow_php', 'php_parse_location'));
        
        return $different;
    }
}

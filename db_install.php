<?php
$SSI_INSTALL = false;
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
{
	$SSI_INSTALL = true;
	require_once(dirname(__FILE__) . '/SSI.php');
}
elseif (!defined('SMF')) // If we are outside SMF and can't find SSI.php, then throw an error
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as SMF\'s SSI.php.');

//==============================================================================
// SleePy's technique for single database installer for v1.1.x and 2.0.x:
//==============================================================================
if(!function_exists('db_query'))
{
	db_extend('Packages');
	function db_query($query, $file, $line)
	{
		global $smcFunc;
		return $smcFunc['db_query']('', $query, array('db_error_skip' => true));
	}
}
// All work here is for back support for SMF 1.1, It is easier to support 2.0 and backport.
else
{
	$smcFunc = $func;
	$smcFunc['db_num_rows'] = 'mysql_num_rows';
	$smcFunc['db_free_result'] = 'mysql_free_result';
	$smcFunc['db_fetch_assoc'] = 'mysql_fetch_assoc';
	$smcFunc['db_list_columns'] = 'mysql_show_columns';
	$smcFunc['db_add_column'] = 'mysql_create_columns';

	// Quickly emulate these functions.
	function mysql_show_columns($table_name)
	{
		global $smcFunc, $db_prefix;

		$result = db_query("SHOW FIELDS FROM {$table_name}", __FILE__, __LINE__);;
		$columns = array();
		while ($row = $smcFunc['db_fetch_assoc']($result))
			$columns[] = $row['Field'];
		return $columns;
	}
	function mysql_create_columns($table_name, $column_info)
	{
		global $db_prefix;

		return db_query('ALTER IGNORE TABLE ' . str_replace('{db_prefix}', $db_prefix, $table_name) . '
			ADD ' . $column_info['name'] . ' ' . $column_info['type'] . ' ' . (empty($column_info['null']) ? 'NOT NULL' : '') . ' ' .
		(empty($column_info['default']) ? '' : 'default \'' . $column_info['default'] . '\'') . ' ' .
		(empty($column_info['auto']) ? '' : 'auto_increment') . ' ', __FILE__, __LINE__);
	}
}

//==============================================================================
// Insert one column into the necessary tables:
//==============================================================================
// {prefix}boards table gets a new column to hold the number of anonymous posts:
$smcFunc['db_add_column'](
	'{db_prefix}smileys', 
	array(
		'name' => 'aliases', 
		'type' => 'TEXT',
	)
);

//==============================================================================
// Set the default value for the PAM mode if not already set:
//==============================================================================
if (!isset($modSettings['PAM_mode']))
	updateSettings(	array( 'PAM_mode' => 3 ) );

// Echo that we are done if necessary:
if ($SSI_INSTALL)
	echo 'DB Changes should be made now...';
?>
<?php
add_action('admin_menu', 'pma_add_tables_menus');

function pma_add_tables_menus() {
    global $wpdb;

    $mainQuery = $wpdb->get_results('SHOW TABLES FROM `' . DB_NAME . '`', ARRAY_A);

    foreach ($mainQuery as $main) {
        foreach ($main as $t) {
            $t = str_replace($wpdb->prefix, '', $t);
            add_submenu_page(__FILE__, $t, $t, 'manage_options', "$t-top-level-handle", 'pma_selected_table_manager');
        }
    }
}

function pma_selected_table_manager() {
    global $wpdb;

    if (($index = strrpos($_GET['page'], '-top-level-handle')) !== false) {
        $table_name = substr($_GET['page'], 0, $index);

        $prefix = $wpdb->prefix;

        if ($_POST[$prefix . $table_name . '_action'] == 'add') {
            pma_add_values($prefix . $table_name);
        }
        if ($_POST[$prefix . $table_name . '_action'] == 'detail') {
            pma_show_detail($prefix . $table_name);
        }
        if ($_POST[$prefix . $table_name . '_action'] == 'delete') {
            pma_delete_entry($prefix . $table_name);
        }
        if ($_POST[$prefix . $table_name . '_action'] == 'change') {
            pma_table_exists($prefix . $table_name);
            pma_change_entry($prefix . $table_name);
        }

        if ($_POST[ $prefix . $table_name . '_action' ] != 'detail') {
            pma_show_table( $prefix . $table_name);
        }
    } else {
        echo "<p>Error: table name not found.</p>";
    }
}

function pma_table_exists( $table_name ) {
	global $wpdb;
	$sql = "SHOW TABLES LIKE '$table_name';";
	$result = $wpdb->get_results( $sql );
	if ( count( $result ) > 0 ) {
		return True;
	} else {
		return False;
	}
}

function pma_manage_table_list() {
    global $wpdb;

    echo '<p>Select a table to edit below:</p>
    <p>';
        $mainQuery = $wpdb->get_results('SHOW TABLES FROM `' . DB_NAME . '`', ARRAY_A);
        foreach ($mainQuery as $main) {
            foreach ($main as $t) {
                echo '<a href="' . admin_url('admin.php?page=' . str_replace($wpdb->prefix, '', $t) . '-top-level-handle') . '"><code>' . $t . '</code></a><br>';
            }
        }
    echo '</p>';
}

function pma_show_table($table_name) {
    global $wpdb;

    $primary_key_name = pma_get_primary_key_name($table_name);

    $columns = $wpdb->get_results("SHOW COLUMNS FROM `$table_name`;");
    $rows = $wpdb->get_results("SELECT * FROM `$table_name`;" , ARRAY_A);

    echo '<div class="wrap">
	<h2>Portable phpMyAdmin</h2>
    <h4>Currently editing table <code>' . $table_name . '</code>.</h4>

    <table class="wp-list-table widefat posts">
        <thead>
            <tr>';
                foreach ($columns as $column) {
                    echo '<th>' . $column->Field . '</th>';
                }
            echo '</tr>
        </thead>
        <tbody>';
            foreach ($rows as $row) {
                echo '<tr>
                    <td>
                        <form method="post">
                            <input type="hidden" name="pma_table_name" value="' . $table_name . '">
                            <input type="hidden" name="' . $table_name . '_action" value="detail">
                            <input type="hidden" name="' . $table_name . '_' . $primary_key_name . '" value="' . $row[$primary_key_name] . '">
                            <input type="submit" class="button button-secondary" value="Edit">
                        </form>
                    </td>';

                    foreach ($row as $index => $value) {
                        if ($index != $primary_key_name) {
                            if (strlen($value) > 60) {
                                echo '<td>' . substr($value, 0, 60) . '&hellip;</td>';
                            } else {
                                echo "<td>$value</td>";
                            }
                        }
                    }
                echo '</tr>';
            }

            //* Print out form to add entries to the table:
            echo '<tr>
                <form method="post">
                    <input type="hidden" name="pma_table_name" value="' . $table_name . '">
                    <input type="hidden" name="' . $table_name . '_action" value="add">';
                    foreach ($columns as $column) {
                        if ($column->Field == $primary_key_name) {
                            echo '<td><input type="submit" class="button button-secondary" value="Add"></td>';
                        } else {
                            echo '<td><input type="text" name="' . $column->Field . '"></td>';
                        }
                    }
                echo '</form>
            </tr>
        </tbody>
    </table>
    </div>';
}

function pma_add_values($table_name) {
    global $wpdb;

    if ($_POST[$table_name . '_action'] == 'add') {
        $primary_key_name = pma_get_primary_key_name($table_name);

        $columns = $wpdb->get_results("SHOW COLUMNS FROM `$table_name`;");
        $values = array();

        foreach ($columns as $column) {
            $index = $column->Field;
            if (isset($_POST[$index])) {
                $values[$column->Field] = $_POST[$index];
            }
        }
        echo "<p>inserting to $prefix$table_name</p>";
        $wpdb->insert($prefix . $table_name, $values);
    }
}

function pma_show_detail($table_name) {
    global $wpdb;

    $primary_key_name = pma_get_primary_key_name($table_name);

    echo '<div class="wrap">
        <h2>Portable phpMyAdmin</h2>
        <h4>Currently editing table <code>' . $table_name . '</code>.</h4>';

        if (isset($_POST[$table_name . '_' . $primary_key_name])) {
            $primary_key_value = $_POST[$table_name . '_' . $primary_key_name];

            $row = $wpdb->get_row("SELECT * FROM `$table_name` WHERE `$primary_key_name`='$primary_key_value';" , ARRAY_A);
            echo '<table>
                <form method="post">
                    <input type="hidden" name="pma_table_name" value="' . $table_name . '">
                    <input type="hidden" name="' . $table_name . '_action" value="change">
                    <input type="hidden" name="' . $table_name . '_' . $primary_key_name . '" value="' . $primary_key_value . '">';
                    foreach ($row as $index => $value) {
                        if ($index == $primary_key_name) {
                            echo "<tr><th>$index</th><td>$value</td></tr>";
                        } else {
                            echo "<tr><th>$index</th><td><input type='text' name='$index' value='$value'></td><tr>";
                        }
                    }
                    echo '<tr><td><input type="submit" value="Change"></td><td></td></tr>
                </form>

                <form method="post">
                    <input type="hidden" name="pma_table_name" value="' . $table_name . '">
                    <input type="hidden" name="' . $table_name . '_action" value="delete">
                    <input type="hidden" name="' . $table_name . '_' . $primary_key_name . '" value="' . $primary_key_value . '">
                    <tr><td><input type="submit" value="delete"></td><td></td></tr>
                </form>
            </table>';
        } else {
            echo '<p>No primary key set.</p>';
        }
    echo '</div>';
}

function pma_change_entry($table_name) {
    global $wpdb;

    $primary_key_name = pma_get_primary_key_name($table_name);

    if (($_POST[$table_name . '_action'] == 'change') && isset($_POST[$table_name . '_' . $primary_key_name])) {
        $primary_key_value = $_POST[$table_name . '_' . $primary_key_name];

        $columns = $wpdb->get_results("SHOW COLUMNS FROM `$table_name`;");
        $values = array();

        foreach ($columns as $column) {
            $index = $column->Field;
            if (isset($_POST[$index])) {
                $values[$column->Field] = $_POST[$index];
            }
        }

        echo "<p>Updating $table_name</p>";
        $wpdb->update($table_name, $values, array($primary_key_name => $primary_key_value));
    } else {
        echo '<p>Entry cannot be updated.</p>';
    }
}

function pma_delete_entry($table_name) {
    global $wpdb;

    $primary_key_name = pma_get_primary_key_name($table_name);

    if (($_POST[$table_name . '_action'] == 'delete') && isset($_POST[$table_name . '_' . $primary_key_name])) {
        $primary_key_value = $_POST[$table_name . '_' . $primary_key_name];
        $wpdb->query("DELETE FROM `$table_name` WHERE `$primary_key_name`='$primary_key_value';");

        echo '<p>Entry deleted.</p>';
    }
}

function pma_get_primary_key_name($table_name) {
    global $wpdb;

    $primary_key_info = $wpdb->get_row("SHOW KEYS FROM `$table_name` WHERE Key_name = 'PRIMARY';");
    $primary_key_name = $primary_key_info->Column_name;

    return $primary_key_name;
}

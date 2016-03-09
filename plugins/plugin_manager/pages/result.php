<?php
if (isset($dependent_plugins)) {
    echo "<b>" . $dependent_plugins[0] . "</b> is a (indirect or direct) dependency for <b>" . implode(", ", array_slice($dependent_plugins, 1)) . "</b>.<br>The dependent plugins also have to be disabled, continue?";
    ?>
    <form action="<?php echo BASE_URL . "/admin/plugins/disable"; ?>" method="post">
        <input type='hidden' name='plugins' id='plugins' value="<?php echo htmlentities(serialize($dependent_plugins)); ?>" />
        <input type="submit" name="action" value="Disable" />
        <input type="submit" name="action" value="Cancel" />
    </form>
<?php
} else if (isset($dependencies)) {
    echo "<b>" . $dependencies[0] . "</b> is dependent on <b>" . implode(", ", array_slice($dependencies, 1)) . "</b>.<br>Dependencies also have to be disabled, continue?";
    ?>
    <form action="<?php echo BASE_URL . "/admin/plugins/disable"; ?>" method="post">
        <input type='hidden' name='plugins' id='plugins' value="<?php echo htmlentities(serialize($dependencies)); ?>" />
        <input type="submit" name="action" value="Enable" />
        <input type="submit" name="action" value="Cancel" />
    </form>
<?php
} else {
    echo $result_message;
    echo "<br><a href='" . BASE_URL . "/admin/plugins'> go back to plugin list </a>";
}

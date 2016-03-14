<?php
if (isset($dependent_plugins)) {
    echo "<p><b>" . $dependent_plugins[0] . "</b> is a (indirect or direct) dependency for <b>" . implode(", ", array_slice($dependent_plugins, 1)) . "</b>.<br>The dependent plugins also have to be disabled, continue?</p>";
    ?>
    <form action="<?php echo BASE_URL . "admin/plugins/mdisable"; ?>" method="post">
        
        <input type='hidden' name='plugins' id='plugins' value="<?php echo htmlentities(serialize($dependent_plugins)); ?>" />
        <button type="submit" name="action" class="btn btn-default" value="Cancel">Cancel</button>
        <button type="submit" name="action" class="btn btn-danger" value="Disable">Disable</button>
    </form>
<?php
} else if (isset($dependencies)) {
    echo "<p><b>" . $dependencies[0] . "</b> is dependent on <b>" . implode(", ", array_slice($dependencies, 1)) . "</b>.<br>Dependencies also have to be disabled, continue?</p>";
    ?>
    <form action="<?php echo BASE_URL . "admin/plugins/mdisable"; ?>" method="post">
        <input type='hidden' name='plugins' id='plugins' value="<?php echo htmlentities(serialize($dependencies)); ?>" />
        <button type="submit" name="action" class="btn btn-default" value="Cancel">Cancel</button>
        <button type="submit" name="action" class="btn btn-success" value="Enable">Enable</button>
    </form>
<?php 
} else {
    header("Location: " . BASE_URL . "admin/plugins");
}
?>